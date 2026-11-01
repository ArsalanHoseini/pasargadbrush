<?php

namespace App\Services\Checkout;

use App\Mail\OrderUpdated;
use App\Models\Order;
use App\Services\Sms\SmsSender;
use App\Support\Money;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Tells the customer what changed on their order after staff edit it: a new
 * status the customer cares about and/or the final price being confirmed —
 * combined into one SMS (+ email when the order has an address), so one save
 * never sends two texts. Uses the contact details entered on the order.
 * Each channel's failure is logged and never breaks saving the order.
 */
class NotifyOrderUpdated
{
    /**
     * What each status means to the customer; "pending" is never announced
     * (it's where every order starts).
     */
    public const STATUS_MESSAGES = [
        'confirmed' => 'تأیید شد و به‌زودی آماده‌سازی آن آغاز می‌شود.',
        'processing' => 'در حال آماده‌سازی است.',
        'shipped' => 'ارسال شد.',
        'completed' => 'تحویل داده شد. از خرید شما سپاسگزاریم.',
        'cancelled' => 'لغو شد. برای اطلاعات بیشتر با ما تماس بگیرید.',
    ];

    public function __construct(private SmsSender $sms) {}

    /**
     * @return bool Whether there was anything to tell the customer.
     */
    public function handle(Order $order, bool $statusChanged, bool $priceConfirmed): bool
    {
        $lines = $this->lines($order, $statusChanged, $priceConfirmed);

        if ($lines === []) {
            return false;
        }

        $order->loadMissing('items', 'shippingAddress');

        if ($order->customer_phone) {
            $this->attempt('sms', $order, fn () => $this->sms->send(
                $order->customer_phone,
                setting('site_name', 'پاسارگاد برس').": سفارش {$order->order_number}\n"
                    .implode("\n", $lines)."\n"
                    .'پیگیری: '.route('orders.track.create'),
            ));
        }

        if ($order->customer_email) {
            $this->attempt('email', $order, fn () => Mail::to($order->customer_email)->send(new OrderUpdated($order, $lines)));
        }

        return true;
    }

    /**
     * @return list<string>
     */
    public function lines(Order $order, bool $statusChanged, bool $priceConfirmed): array
    {
        $lines = [];

        if ($statusChanged && isset(self::STATUS_MESSAGES[$order->status])) {
            $lines[] = 'سفارش شما '.self::STATUS_MESSAGES[$order->status];

            if ($order->status === 'shipped' && $order->shipment_tracking_code) {
                $lines[] = 'کد رهگیری مرسوله: '.$order->shipment_tracking_code;
            }
        }

        if ($priceConfirmed) {
            $shipping = $order->shippingQuote();
            $lines[] = $shipping->isTotalKnown()
                ? 'قیمت نهایی تأیید شد — مبلغ کل: '.Money::toman($order->total_rial)
                : 'قیمت نهایی کالاها تأیید شد — جمع کالاها: '.Money::toman($order->subtotal_rial).' (هزینه ارسال جداگانه اعلام می‌شود)';
        }

        return $lines;
    }

    private function attempt(string $channel, Order $order, callable $send): void
    {
        try {
            $send();
        } catch (\Throwable $e) {
            Log::warning("Failed to send order update notification ({$channel})", [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
