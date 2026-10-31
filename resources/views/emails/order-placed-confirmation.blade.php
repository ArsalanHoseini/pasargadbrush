<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Tahoma, sans-serif; font-size: 14px; color: #1d1d1d;">
    <h2>سفارش شما با موفقیت ثبت شد</h2>

    <p>{{ $order->customer_name ?? $order->customer?->name }} عزیز، از خرید شما سپاسگزاریم.</p>
    <p><strong>شماره سفارش:</strong> <span dir="ltr">{{ $order->order_number }}</span></p>

    @include('emails.partials.order-summary')

    <p>همکاران ما به‌زودی برای هماهنگی پرداخت و ارسال با شما تماس خواهند گرفت.</p>

    <hr>
    <p style="color: #888;">
        {{ setting('site_name', 'پاسارگاد برس') }}
        @if (setting('contact_mobile'))
            — <span dir="ltr">{{ setting('contact_mobile') }}</span>
        @endif
    </p>
</body>
</html>
