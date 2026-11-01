@extends('layouts.public')

@section('title', 'سفارش ثبت شد')

@section('content')
<div class="mx-auto max-w-2xl px-4 py-16 text-center sm:px-6">
    <div class="mb-6 inline-flex h-16 w-16 items-center justify-center rounded-full bg-green-50 text-green-600">
        <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </div>

    <h1 class="mb-2 text-2xl font-bold text-neutral-900">سفارش شما با موفقیت ثبت شد</h1>
    <p class="mb-1 text-sm text-neutral-500">شماره سفارش: <span dir="ltr" class="font-medium">{{ $order->order_number }}</span></p>
    <p class="mb-8 text-sm text-neutral-600">
        همکاران ما به‌زودی برای هماهنگی پرداخت و ارسال با شما تماس خواهند گرفت.
    </p>

    <div class="mx-auto max-w-md rounded-lg border border-neutral-200 bg-white p-6 text-right">
        <h2 class="mb-3 text-sm font-bold text-neutral-900">اقلام سفارش</h2>
        <ul class="divide-y divide-neutral-100 text-sm">
            @foreach ($order->items as $item)
                <li class="flex justify-between py-2">
                    <span>{{ $item->name_snapshot }} × {{ $item->quantity }}</span>
                    <span dir="ltr">{{ \App\Support\Money::toman($item->line_total_rial) }}</span>
                </li>
            @endforeach
        </ul>
        <div class="mt-3 border-t border-neutral-100 pt-3">
            @include('orders.partials.totals', ['subtotalRial' => $order->subtotal_rial, 'shipping' => $order->shippingQuote()])
        </div>
    </div>

    <p class="mx-auto mt-6 max-w-md text-xs text-neutral-500">
        شماره سفارش را نگه دارید — هر زمان می‌توانید با آن و شماره موبایل خود وضعیت سفارش را در صفحه‌ی
        <a href="{{ route('orders.track.create') }}" class="text-brand-accent-dark hover:underline">پیگیری سفارش</a>
        ببینید.
    </p>

    <a href="{{ route('home') }}" class="mt-8 inline-block text-sm text-brand-accent-dark hover:underline">
        بازگشت به صفحه اصلی
    </a>
</div>
@endsection
