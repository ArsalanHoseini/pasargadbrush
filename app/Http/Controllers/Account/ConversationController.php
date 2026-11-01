<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\MessageAttachment;
use App\Models\Order;
use App\Models\QuoteRequest;
use App\Services\Messaging\ConversationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The customer's side of staff ↔ customer messaging. Every lookup is scoped
 * to the signed-in customer; anything else is a 404.
 */
class ConversationController extends Controller
{
    public function __construct(private ConversationService $conversations) {}

    public function index(Request $request): View
    {
        return view('account.conversations.index', [
            'conversations' => $this->customer($request)->conversations()
                ->with('order', 'quoteRequest.product')
                ->latest('last_message_at')
                ->paginate(15),
        ]);
    }

    /**
     * New general ticket, or (with ?order= / ?quote_request=) a conversation
     * about one of the customer's orders or quote requests — reusing the
     * existing one if there is.
     */
    public function create(Request $request): View|RedirectResponse
    {
        [$order, $quoteRequest] = $this->subjectFromRequest($request);

        $existing = $order?->conversation ?? $quoteRequest?->conversation;

        if ($existing) {
            return redirect()->route('account.conversations.show', $existing);
        }

        return view('account.conversations.create', [
            'order' => $order,
            'quoteRequest' => $quoteRequest,
            'subject' => match (true) {
                $order !== null => 'سفارش '.$order->order_number,
                $quoteRequest !== null => 'استعلام قیمت — '.($quoteRequest->product?->name ?? 'درخواست عمومی'),
                default => '',
            },
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        [$order, $quoteRequest] = $this->subjectFromRequest($request);

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            ...ConversationService::messageRules(),
        ], attributes: ConversationService::messageAttributes());

        $conversation = $this->conversations->start(
            $this->customer($request),
            $this->customer($request),
            $data['subject'],
            $data['body'],
            $request->file('attachments', []),
            $order,
            $quoteRequest,
        );

        return redirect()->route('account.conversations.show', $conversation)->with('status', 'پیام شما ارسال شد. پاسخ کارشناس را اینجا خواهید دید.');
    }

    public function show(Request $request, Conversation $conversation): View
    {
        $this->authorizeConversation($request, $conversation);

        if ($conversation->customer_unread) {
            $conversation->update(['customer_unread' => false]);
        }

        return view('account.conversations.show', [
            'conversation' => $conversation->load('messages.attachments', 'messages.user', 'order', 'quoteRequest.product'),
        ]);
    }

    public function reply(Request $request, Conversation $conversation): RedirectResponse
    {
        $this->authorizeConversation($request, $conversation);

        $data = $request->validate(ConversationService::messageRules(), attributes: ConversationService::messageAttributes());

        $this->conversations->post($conversation, $this->customer($request), $data['body'], $request->file('attachments', []));

        return redirect()->route('account.conversations.show', $conversation)->with('status', 'پیام شما ارسال شد.');
    }

    public function attachment(Request $request, MessageAttachment $attachment): StreamedResponse
    {
        $this->authorizeConversation($request, $attachment->message->conversation);

        return Storage::disk($attachment->disk)->response($attachment->path, $attachment->original_name, [
            'Content-Type' => $attachment->mime_type,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function customer(Request $request): Customer
    {
        return $request->user('customer');
    }

    private function authorizeConversation(Request $request, Conversation $conversation): void
    {
        abort_unless($conversation->customer_id === $this->customer($request)->id, 404);
    }

    /**
     * @return array{0: ?Order, 1: ?QuoteRequest}
     */
    private function subjectFromRequest(Request $request): array
    {
        $customer = $this->customer($request);
        $order = null;
        $quoteRequest = null;

        if ($request->filled('order')) {
            $order = $customer->orders()->findOrFail($request->integer('order'));
        } elseif ($request->filled('quote_request')) {
            $quoteRequest = QuoteRequestController::visibleTo($customer)->findOrFail($request->integer('quote_request'));
        }

        return [$order, $quoteRequest];
    }
}
