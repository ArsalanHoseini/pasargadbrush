@php
    $recipientName = $order->shippingAddress?->recipient_name ?: ($order->customer_name ?? $order->customer?->name);
    $recipientPhone = $order->shippingAddress?->recipient_phone ?: ($order->customer_phone ?? $order->customer?->phone);
    $isInvoice = $document === 'invoice';
@endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $isInvoice ? 'فاکتور' : 'برچسب ارسال' }} {{ $order->order_number }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @page { size: {{ $isInvoice ? 'A4' : 'A6' }}; margin: 12mm; }
        @media print { .no-print { display: none !important; } body { background: #fff !important; } }
    </style>
</head>
<body class="bg-neutral-100 text-neutral-900 antialiased print:bg-white">
<div class="no-print mx-auto flex max-w-3xl items-center justify-between gap-3 px-4 py-4 text-sm">
    <a href="{{ route('admin.orders.edit', $order) }}" class="text-neutral-600 hover:text-neutral-900">→ بازگشت به سفارش</a>
    <button type="button" onclick="window.print()" class="rounded-lg bg-brand-accent-dark px-4 py-2 font-medium text-white hover:bg-brand-accent-darker">چاپ</button>
</div>

@if ($isInvoice)
    <main class="mx-auto max-w-3xl bg-white p-8 shadow-sm print:max-w-none print:p-0 print:shadow-none">
        <header class="flex items-start justify-between gap-6 border-b-2 border-neutral-900 pb-4">
            <div>
                <img src="{{ asset('images/logo.webp') }}" alt="{{ setting('site_name') }}" class="mb-2 h-12 w-auto">
                <p class="text-sm font-semibold">{{ setting('company_legal_name') }}</p>
                <p class="text-xs text-neutral-600">{{ setting('contact_address') }}</p>
                <p class="text-xs text-neutral-600" dir="ltr">{{ collect([setting('contact_landline'), setting('contact_mobile')])->filter()->implode(' — ') }}</p>
            </div>
            <div class="text-left">
                <h1 class="text-xl font-bold">فاکتور فروش</h1>
                <p class="mt-1 text-sm" dir="ltr">{{ $order->order_number }}</p>
                <p class="text-xs text-neutral-600">تاریخ: {{ persian_date($order->placed_at, \App\Support\PersianDate::COMPACT_DATE) }}</p>
                @if ($order->requires_price_confirmation)
                    <p class="mt-2 rounded border border-amber-300 px-2 py-1 text-xs text-amber-800">پیش‌فاکتور — قیمت نهایی هنوز تأیید نشده</p>
                @endif
            </div>
        </header>

        <section class="grid grid-cols-2 gap-6 py-4 text-sm">
            <div>
                <h2 class="mb-1 text-xs font-medium text-neutral-500">خریدار</h2>
                <p class="font-medium">{{ $order->customer_name ?? $order->customer?->name }}</p>
                @if ($order->customer?->company_name)
                    <p>{{ $order->customer->company_name }}</p>
                @endif
                <p dir="ltr" class="text-right">{{ $order->customer_phone ?? $order->customer?->phone }}</p>
            </div>
            @if ($order->shippingAddress)
                <div>
                    <h2 class="mb-1 text-xs font-medium text-neutral-500">آدرس ارسال</h2>
                    <p>{{ $order->shippingAddress->province }}، {{ $order->shippingAddress->city }}</p>
                    <p>{{ $order->shippingAddress->address_line }}</p>
                    @if ($order->shippingAddress->postal_code)
                        <p>کد پستی: <span dir="ltr">{{ $order->shippingAddress->postal_code }}</span></p>
                    @endif
                </div>
            @endif
        </section>

        <table class="w-full border-collapse text-sm">
            <thead>
                <tr class="border-y border-neutral-300 bg-neutral-50 text-right text-xs">
                    <th class="px-2 py-2 font-medium">#</th>
                    <th class="px-2 py-2 font-medium">شرح کالا</th>
                    <th class="px-2 py-2 font-medium">کد</th>
                    <th class="px-2 py-2 font-medium">تعداد</th>
                    <th class="px-2 py-2 font-medium">قیمت واحد</th>
                    <th class="px-2 py-2 font-medium">جمع</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr class="border-b border-neutral-200 align-top">
                        <td class="px-2 py-2">{{ $loop->iteration }}</td>
                        <td class="px-2 py-2">
                            {{ $item->name_snapshot }}
                            @include('orders.partials.customization', ['customization' => $item->customization])
                        </td>
                        <td class="px-2 py-2 text-xs" dir="ltr">{{ $item->sku_snapshot }}</td>
                        <td class="px-2 py-2">{{ $item->quantity }}</td>
                        <td class="whitespace-nowrap px-2 py-2" dir="ltr">{{ \App\Support\Money::toman($item->unit_price_rial) }}</td>
                        <td class="whitespace-nowrap px-2 py-2" dir="ltr">{{ \App\Support\Money::toman($item->line_total_rial) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mr-auto mt-4 w-72 space-y-1 text-sm">
            <div class="flex justify-between"><span>جمع اقلام</span><span dir="ltr">{{ \App\Support\Money::toman($order->subtotal_rial) }}</span></div>
            <div class="flex justify-between"><span>هزینه ارسال</span><span>{{ $shipping->description() }}</span></div>
            <div class="flex justify-between border-t border-neutral-900 pt-1 font-bold">
                <span>مبلغ قابل پرداخت</span>
                <span dir="ltr">{{ $shipping->isTotalKnown() ? \App\Support\Money::toman($order->total_rial) : '—' }}</span>
            </div>
            <div class="flex justify-between text-xs text-neutral-600"><span>وضعیت پرداخت</span><span>{{ \App\Models\Order::PAYMENT_STATUS_LABELS[$order->payment_status] ?? $order->payment_status }}</span></div>
        </div>

        <footer class="mt-12 grid grid-cols-2 gap-6 text-center text-xs text-neutral-600">
            <div class="border-t border-neutral-300 pt-2">مهر و امضای فروشنده</div>
            <div class="border-t border-neutral-300 pt-2">امضای خریدار</div>
        </footer>
    </main>
@else
    <main class="mx-auto max-w-sm border-2 border-neutral-900 bg-white p-5 text-sm print:max-w-none print:border">
        <div class="mb-3 flex items-center justify-between border-b border-neutral-300 pb-3">
            <img src="{{ asset('images/logo.webp') }}" alt="{{ setting('site_name') }}" class="h-8 w-auto">
            <span class="font-bold" dir="ltr">{{ $order->order_number }}</span>
        </div>
        <h2 class="text-xs text-neutral-500">گیرنده</h2>
        <p class="text-lg font-bold">{{ $recipientName }}</p>
        <p class="text-base" dir="ltr" style="text-align: right">{{ $recipientPhone }}</p>
        @if ($order->shippingAddress)
            <p class="mt-2 text-base">{{ $order->shippingAddress->province }}، {{ $order->shippingAddress->city }}</p>
            <p>{{ $order->shippingAddress->address_line }}</p>
            @if ($order->shippingAddress->postal_code)
                <p class="mt-1 font-bold">کد پستی: <span dir="ltr">{{ $order->shippingAddress->postal_code }}</span></p>
            @endif
        @else
            <p class="mt-2 text-neutral-500">آدرسی برای این سفارش ثبت نشده است.</p>
        @endif
        <div class="mt-4 border-t border-neutral-300 pt-3 text-xs">
            <h2 class="text-neutral-500">فرستنده</h2>
            <p class="font-medium">{{ setting('company_legal_name') }}</p>
            <p>{{ setting('contact_address') }}</p>
            <p dir="ltr" style="text-align: right">{{ setting('contact_mobile') }}</p>
        </div>
        <p class="mt-3 text-xs text-neutral-600">{{ $order->items->sum('quantity') }} قلم کالا — {{ $shipping->method->label() }}</p>
    </main>
@endif
</body>
</html>
