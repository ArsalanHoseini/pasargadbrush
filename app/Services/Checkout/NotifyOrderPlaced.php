<?php

namespace App\Services\Checkout;

use App\Mail\NewOrderReceived;
use App\Mail\OrderPlacedConfirmation;
use App\Models\Order;
use App\Services\Sms\SmsSender;
use App\Support\Money;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Tells the business and the customer that a new order was placed, by email
 * and SMS. Runs after the order is committed; every channel is isolated so a
 * failing mail transport or SMS provider never loses the order itself — it
 * is already saved and visible in the admin panel.
 */
class NotifyOrderPlaced
{
    public function __construct(private SmsSender $sms) {}

    public function handle(Order $order): void
    {
        $order->loadMissing('customer', 'items', 'shippingAddress');

        $adminEmail = setting('contact_email');
        $adminMobile = setting('contact_mobile');

        if ($adminEmail) {
            $this->attempt('admin email', $order, fn () => Mail::to($adminEmail)->send(new NewOrderReceived($order)));
        }

        if ($adminMobile) {
            $this->attempt('admin sms', $order, fn () => $this->sms->send($adminMobile, $this->adminSmsText($order)));
        }

        // The contact details entered on this order, not the customer record —
        // the record is never updated by checkout (see PlaceOrder).
        if ($order->customer_email) {
            $this->attempt('customer email', $order, fn () => Mail::to($order->customer_email)->send(new OrderPlacedConfirmation($order)));
        }

        if ($order->customer_phone) {
            $this->attempt('customer sms', $order, fn () => $this->sms->send($order->customer_phone, $this->customerSmsText($order)));
        }
    }

    private function adminSmsText(Order $order): string
    {
        return "سفارش جدید {$order->order_number}\n"
            .$order->customer_name.' — '.$order->customer_phone."\n"
            .'مبلغ: '.Money::toman($order->total_rial);
    }

    private function customerSmsText(Order $order): string
    {
        return setting('site_name', 'پاسارگاد برس').": سفارش شما با شماره {$order->order_number} ثبت شد. "
            .'همکاران ما به‌زودی برای هماهنگی پرداخت و ارسال با شما تماس می‌گیرند.';
    }

    private function attempt(string $channel, Order $order, callable $send): void
    {
        try {
            $send();
        } catch (\Throwable $e) {
            Log::warning("Failed to send order notification ({$channel})", [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
