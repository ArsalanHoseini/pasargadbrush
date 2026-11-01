<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Tahoma, sans-serif; font-size: 14px; color: #1d1d1d;">
    <h2>به‌روزرسانی سفارش <span dir="ltr">{{ $order->order_number }}</span></h2>

    <p>{{ $order->customer_name }} عزیز،</p>
    @foreach ($lines as $line)
        <p style="font-weight: bold;">{{ $line }}</p>
    @endforeach

    @include('emails.partials.order-summary')

    <p>
        وضعیت سفارش را هر زمان در صفحه‌ی
        <a href="{{ route('orders.track.create') }}">پیگیری سفارش</a>
        (با شماره سفارش و شماره موبایل) یا در حساب کاربری خود ببینید.
    </p>

    <hr>
    <p style="color: #888;">
        {{ setting('site_name', 'پاسارگاد برس') }}
        @if (setting('contact_mobile'))
            — <span dir="ltr">{{ setting('contact_mobile') }}</span>
        @endif
    </p>
</body>
</html>
