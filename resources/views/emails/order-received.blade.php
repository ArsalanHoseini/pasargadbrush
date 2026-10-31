<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Tahoma, sans-serif; font-size: 14px; color: #1d1d1d;">
    <h2>سفارش جدید ثبت شد</h2>

    <p><strong>شماره سفارش:</strong> <span dir="ltr">{{ $order->order_number }}</span></p>
    <p><strong>نام:</strong> {{ $order->customer_name ?? $order->customer?->name }}</p>
    <p><strong>تلفن:</strong> <span dir="ltr">{{ $order->customer_phone ?? $order->customer?->phone }}</span></p>
    @if ($order->customer_email ?? $order->customer?->email)
        <p><strong>ایمیل:</strong> {{ $order->customer_email ?? $order->customer?->email }}</p>
    @endif

    @if ($order->shippingAddress)
        <p>
            <strong>آدرس:</strong>
            {{ $order->shippingAddress->province }}، {{ $order->shippingAddress->city }}، {{ $order->shippingAddress->address_line }}
            @if ($order->shippingAddress->postal_code)
                — کد پستی: <span dir="ltr">{{ $order->shippingAddress->postal_code }}</span>
            @endif
        </p>
    @endif

    @include('emails.partials.order-summary')

    <p><a href="{{ route('admin.orders.edit', $order) }}">مشاهده سفارش در پنل مدیریت</a></p>

    <hr>
    <p style="color: #888;">
        این سفارش هنوز پرداخت نشده است — برای هماهنگی پرداخت و ارسال با مشتری تماس بگیرید.
    </p>
</body>
</html>
