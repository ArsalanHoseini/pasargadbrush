<?php

namespace App\Services\Messaging;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Customer;
use App\Models\Order;
use App\Models\QuoteRequest;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Starts staff ↔ customer conversations and posts messages to them. The
 * author decides whose turn it is next: a customer message marks the
 * conversation "open" (staff must reply) and unread for staff; a staff
 * message marks it "answered" and unread for the customer. A customer
 * writing in a closed conversation reopens it.
 */
class ConversationService
{
    public const ATTACHMENT_DISK = 'local';

    public const MAX_ATTACHMENTS = 5;

    /** Kilobytes. */
    public const MAX_ATTACHMENT_SIZE = 10240;

    public const ATTACHMENT_MIMES = 'jpg,jpeg,png,webp,pdf';

    public function __construct(private ConversationNotifier $notifier) {}

    /**
     * The conversation about an order / quote request is reused if it
     * already exists (at most one each); without either it's a new general
     * ticket.
     *
     * @param  array<int, UploadedFile>  $files
     */
    public function start(
        Customer $customer,
        Customer|User $author,
        string $subject,
        string $body,
        array $files = [],
        ?Order $order = null,
        ?QuoteRequest $quoteRequest = null,
    ): Conversation {
        $existing = match (true) {
            $order !== null => Conversation::query()->where('order_id', $order->id)->first(),
            $quoteRequest !== null => Conversation::query()->where('quote_request_id', $quoteRequest->id)->first(),
            default => null,
        };

        $conversation = $existing ?? Conversation::create([
            'customer_id' => $customer->id,
            'order_id' => $order?->id,
            'quote_request_id' => $quoteRequest?->id,
            'subject' => $subject,
        ]);

        $this->post($conversation, $author, $body, $files);

        return $conversation;
    }

    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function post(Conversation $conversation, Customer|User $author, string $body, array $files = []): ConversationMessage
    {
        $fromStaff = $author instanceof User;
        $storedPaths = [];

        try {
            $message = DB::transaction(function () use ($conversation, $author, $body, $files, $fromStaff, &$storedPaths) {
                $message = $conversation->messages()->create([
                    'author_type' => $fromStaff ? ConversationMessage::AUTHOR_STAFF : ConversationMessage::AUTHOR_CUSTOMER,
                    'user_id' => $fromStaff ? $author->id : null,
                    'body' => $body,
                ]);

                foreach ($files as $file) {
                    // Random stored name; the original name is kept only as metadata.
                    $path = $file->storeAs(
                        'conversations/'.$conversation->id,
                        Str::random(40).'.'.$file->extension(),
                        self::ATTACHMENT_DISK,
                    );
                    $storedPaths[] = $path;

                    $message->attachments()->create([
                        'disk' => self::ATTACHMENT_DISK,
                        'path' => $path,
                        'original_name' => Str::limit(basename($file->getClientOriginalName()), 200, ''),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                    ]);
                }

                $conversation->update([
                    'status' => $fromStaff ? Conversation::STATUS_ANSWERED : Conversation::STATUS_OPEN,
                    'staff_unread' => ! $fromStaff,
                    'customer_unread' => $fromStaff,
                    'last_message_at' => now(),
                ]);

                return $message;
            });
        } catch (\Throwable $e) {
            Storage::disk(self::ATTACHMENT_DISK)->delete($storedPaths);

            throw $e;
        }

        $fromStaff ? $this->notifier->notifyCustomer($message) : $this->notifier->notifyStaff($message);

        return $message;
    }

    /**
     * Shared validation rules for a message body and its attachments.
     *
     * @return array<string, array<int, string>>
     */
    public static function messageRules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:'.self::MAX_ATTACHMENTS],
            'attachments.*' => ['file', 'mimes:'.self::ATTACHMENT_MIMES, 'max:'.self::MAX_ATTACHMENT_SIZE],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messageAttributes(): array
    {
        return [
            'body' => 'متن پیام',
            'subject' => 'موضوع',
            'attachments' => 'فایل‌های پیوست',
            'attachments.*' => 'فایل پیوست',
        ];
    }
}
