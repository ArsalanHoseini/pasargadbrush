@extends('layouts.public')

@section('title', 'وضعیت سفارش '.$order->order_number)

@php
    $steps = [
        'pending' => ['label' => 'ثبت شد', 'detail' => 'سفارش شما ثبت شده است. همکاران ما به‌زودی برای هماهنگی پرداخت و ارسال با شما تماس می‌گیرند.'],
        'confirmed' => ['label' => 'تأیید شد', 'detail' => 'سفارش شما تأیید شده و به‌زودی آماده‌سازی آن آغاز می‌شود.'],
        'processing' => ['label' => 'در حال آماده‌سازی', 'detail' => 'سفارش شما در حال آماده‌سازی و بسته‌بندی است.'],
        'shipped' => ['label' => 'ارسال شد', 'detail' => 'سفارش شما ارسال شده و در راه است.'],
        'completed' => ['label' => 'تحویل شد', 'detail' => 'سفارش شما تحویل داده شده است. از خرید شما سپاسگزاریم.'],
    ];
    $isCancelled = $order->status === 'cancelled';
    $currentStepIndex = array_search($order->status, \App\Models\Order::PROGRESS_STEPS, true);
@endphp

@section('content')
<div class="mx-auto max-w-2xl px-4 py-10 sm:px-6">
    <div class="mb-6 flex flex-wrap items-baseline justify-between gap-2">
        <h1 class="text-2xl font-bold text-neutral-900">وضعیت سفارش</h1>
        <a href="{{ route('orders.track.create') }}" class="text-sm text-brand-accent-dark hover:underline">پیگیری سفارش دیگر</a>
    </div>

    <div class="mb-6 rounded-lg border border-neutral-200 bg-white p-5 text-sm">
        <dl class="grid gap-3 sm:grid-cols-3">
            <div>
                <dt class="text-neutral-500">شماره سفارش</dt>
                <dd class="font-medium" dir="ltr">{{ $order->order_number }}</dd>
            </div>
            <div>
                <dt class="text-neutral-500">تاریخ ثبت</dt>
                <dd class="font-medium">{{ \App\Support\PersianDate::format($order->placed_at) }}</dd>
            </div>
            <div>
                <dt class="text-neutral-500">وضعیت پرداخت</dt>
                <dd class="font-medium">{{ \App\Models\Order::PAYMENT_STATUS_LABELS[$order->payment_status] ?? $order->payment_status }}</dd>
            </div>
        </dl>
    </div>

    @if ($isCancelled)
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-5 text-sm text-red-800">
            <p class="font-bold">این سفارش لغو شده است.</p>
            <p class="mt-1">برای اطلاعات بیشتر با ما تماس بگیرید.</p>
        </div>
    @else
        <div class="mb-6 rounded-lg border border-neutral-200 bg-white p-5">
            <ol class="flex items-start justify-between gap-1">
                @foreach (\App\Models\Order::PROGRESS_STEPS as $index => $step)
                    @php $isReached = $currentStepIndex !== false && $index <= $currentStepIndex; @endphp
                    <li class="flex flex-1 flex-col items-center text-center" @if ($index === $currentStepIndex) aria-current="step" @endif>
                        <span @class([
                            'mb-2 flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold',
                            'bg-brand-accent text-white' => $isReached,
                            'bg-neutral-100 text-neutral-400' => ! $isReached,
                        ])>{{ $index + 1 }}</span>
                        <span @class([
                            'text-xs',
                            'font-semibold text-neutral-900' => $isReached,
                            'text-neutral-400' => ! $isReached,
                        ])>{{ $steps[$step]['label'] }}</span>
                    </li>
                @endforeach
            </ol>

            @if ($currentStepIndex !== false)
                <p class="mt-5 rounded bg-neutral-50 p-3 text-sm text-neutral-700">{{ $steps[$order->status]['detail'] }}</p>
            @endif

            @if ($order->shipment_tracking_code)
                <p class="mt-3 text-sm text-neutral-700">
                    کد رهگیری مرسوله:
                    <span class="font-medium" dir="ltr">{{ $order->shipment_tracking_code }}</span>
                </p>
            @endif
        </div>
    @endif

    <div class="mb-6 rounded-lg border border-neutral-200 bg-white p-5">
        <h2 class="mb-3 text-sm font-bold text-neutral-900">اقلام سفارش</h2>
        <ul class="divide-y divide-neutral-100 text-sm">
            @foreach ($order->items as $item)
                <li class="flex justify-between gap-4 py-2">
                    <span>{{ $item->name_snapshot }} × {{ $item->quantity }}</span>
                    <span class="shrink-0" dir="ltr">{{ \App\Support\Money::toman($item->line_total_rial) }}</span>
                </li>
            @endforeach
        </ul>
        @if ($order->shipping_cost_rial > 0)
            <div class="mt-3 flex justify-between border-t border-neutral-100 pt-3 text-sm">
                <span>هزینه ارسال</span>
                <span dir="ltr">{{ \App\Support\Money::toman($order->shipping_cost_rial) }}</span>
            </div>
        @endif
        <div class="mt-3 flex justify-between border-t border-neutral-100 pt-3 font-bold">
            <span>جمع کل</span>
            <span dir="ltr">{{ \App\Support\Money::toman($order->total_rial) }}</span>
        </div>
    </div>

    @if ($order->shippingAddress)
        <div class="mb-6 rounded-lg border border-neutral-200 bg-white p-5 text-sm">
            <h2 class="mb-3 font-bold text-neutral-900">آدرس ارسال</h2>
            <p>{{ $order->shippingAddress->recipient_name }}</p>
            <p class="text-neutral-600">
                {{ $order->shippingAddress->province }}، {{ $order->shippingAddress->city }}، {{ $order->shippingAddress->address_line }}
            </p>
        </div>
    @endif

    @if (setting('contact_mobile'))
        <p class="text-center text-sm text-neutral-500">
            سؤالی درباره‌ی سفارش دارید؟ با
            <a href="tel:{{ setting('contact_mobile') }}" class="text-brand-accent-dark hover:underline" dir="ltr">{{ setting('contact_mobile') }}</a>
            تماس بگیرید.
        </p>
    @endif
</div>
@endsection
