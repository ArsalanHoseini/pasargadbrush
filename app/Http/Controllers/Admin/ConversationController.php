<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\MessageAttachment;
use App\Models\Order;
use App\Models\QuoteRequest;
use App\Services\Messaging\ConversationService;
use App\Support\IranianMobile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The staff side of staff ↔ customer messaging.
 */
class ConversationController extends Controller
{
    public function __construct(private ConversationService $conversations) {}

    public function index(Request $request): View
    {
        $status = $request->query('status');

        return view('admin.conversations.index', [
            'status' => $status,
            'conversations' => Conversation::query()
                ->with('customer', 'order', 'quoteRequest')
                ->when($status, fn ($query) => $query->where('status', $status))
                ->orderByRaw("case when status = 'open' then 0 else 1 end")
                ->latest('last_message_at')
                ->latest('id')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    /**
     * Staff start a conversation about an order, a quote request, or with a
     * customer directly. An existing conversation is reused.
     */
    public function create(Request $request): View|RedirectResponse
    {
        [$customer, $order, $quoteRequest] = $this->targetFromRequest($request);

        $existing = $order?->conversation ?? $quoteRequest?->conversation;

        if ($existing) {
            return redirect()->route('admin.conversations.show', $existing);
        }

        if (! $customer) {
            return back()->with('status', 'این مشتری شماره موبایل معتبری ندارد، پس پیام و اطلاع‌رسانی به او ممکن نیست — لطفاً تلفنی تماس بگیرید.');
        }

        return view('admin.conversations.create', [
            'customer' => $customer,
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
        [$customer, $order, $quoteRequest] = $this->targetFromRequest($request);
        abort_unless($customer !== null, 422);
        $customer->save();

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            ...ConversationService::messageRules(),
        ], attributes: ConversationService::messageAttributes());

        $conversation = $this->conversations->start(
            $customer,
            $request->user(),
            $data['subject'],
            $data['body'],
            $request->file('attachments', []),
            $order,
            $quoteRequest,
        );

        return redirect()->route('admin.conversations.show', $conversation)->with('status', 'پیام ارسال شد و مشتری مطلع شد.');
    }

    public function show(Conversation $conversation): View
    {
        if ($conversation->staff_unread) {
            $conversation->update(['staff_unread' => false]);
        }

        return view('admin.conversations.show', [
            'conversation' => $conversation->load('messages.attachments', 'messages.user', 'customer', 'order', 'quoteRequest.product'),
        ]);
    }

    public function reply(Request $request, Conversation $conversation): RedirectResponse
    {
        $data = $request->validate(ConversationService::messageRules(), attributes: ConversationService::messageAttributes());

        $this->conversations->post($conversation, $request->user(), $data['body'], $request->file('attachments', []));

        return redirect()->route('admin.conversations.show', $conversation)->with('status', 'پاسخ ارسال شد و مشتری مطلع شد.');
    }

    public function updateStatus(Request $request, Conversation $conversation): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in([Conversation::STATUS_CLOSED, Conversation::STATUS_OPEN])]]);

        $conversation->update(['status' => $data['status']]);

        return redirect()->route('admin.conversations.show', $conversation)
            ->with('status', $data['status'] === Conversation::STATUS_CLOSED ? 'گفتگو بسته شد.' : 'گفتگو دوباره باز شد.');
    }

    public function attachment(MessageAttachment $attachment): StreamedResponse
    {
        return Storage::disk($attachment->disk)->response($attachment->path, $attachment->original_name, [
            'Content-Type' => $attachment->mime_type,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * The customer to write to, from ?order=, ?quote_request= or ?customer=.
     * A quote request sent without an account is matched (or a customer
     * record created) by its mobile number, so the customer sees the
     * conversation after logging in with that number (the record is only
     * saved when a message is actually sent); a landline-only request yields
     * no customer.
     *
     * @return array{0: ?Customer, 1: ?Order, 2: ?QuoteRequest}
     */
    private function targetFromRequest(Request $request): array
    {
        if ($request->filled('order')) {
            $order = Order::with('customer', 'conversation')->findOrFail($request->integer('order'));

            return [$order->customer, $order, null];
        }

        if ($request->filled('quote_request')) {
            $quoteRequest = QuoteRequest::with('customer', 'conversation', 'product')->findOrFail($request->integer('quote_request'));

            if ($quoteRequest->customer) {
                return [$quoteRequest->customer, null, $quoteRequest];
            }

            $mobile = IranianMobile::normalize($quoteRequest->phone);

            if (! preg_match(IranianMobile::PATTERN, $mobile)) {
                return [null, null, $quoteRequest];
            }

            $customer = Customer::query()->where('phone', $mobile)->first()
                ?? new Customer(['phone' => $mobile, 'name' => $quoteRequest->name]);

            return [$customer, null, $quoteRequest];
        }

        return [Customer::findOrFail($request->integer('customer')), null, null];
    }
}
