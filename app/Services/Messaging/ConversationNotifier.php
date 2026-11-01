<?php

namespace App\Services\Messaging;

use App\Mail\NewCustomerMessage;
use App\Mail\NewStaffReply;
use App\Models\ConversationMessage;
use App\Services\Sms\SmsSender;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Tells the other side that a new message arrived. A failing channel is
 * logged and never breaks posting — the message is already saved and
 * visible in both dashboards.
 */
class ConversationNotifier
{
    public function __construct(private SmsSender $sms) {}

    /**
     * Customer wrote: email the business (staff also see a badge in the admin nav).
     */
    public function notifyStaff(ConversationMessage $message): void
    {
        $adminEmail = setting('contact_email');

        if ($adminEmail) {
            $this->attempt('staff email', $message, fn () => Mail::to($adminEmail)->send(new NewCustomerMessage($message)));
        }
    }

    /**
     * Staff replied: text the customer (and email them if they have an address).
     */
    public function notifyCustomer(ConversationMessage $message): void
    {
        $conversation = $message->conversation;
        $customer = $conversation->customer;

        $this->attempt('customer sms', $message, fn () => $this->sms->send(
            $customer->phone,
            setting('site_name', 'پاسارگاد برس').": پاسخ جدید کارشناس درباره‌ی «{$conversation->subject}».\n"
                .'مشاهده: '.route('account.conversations.show', $conversation),
        ));

        if ($customer->email) {
            $this->attempt('customer email', $message, fn () => Mail::to($customer->email)->send(new NewStaffReply($message)));
        }
    }

    private function attempt(string $channel, ConversationMessage $message, callable $send): void
    {
        try {
            $send();
        } catch (\Throwable $e) {
            Log::warning("Failed to send conversation notification ({$channel})", [
                'conversation_message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
