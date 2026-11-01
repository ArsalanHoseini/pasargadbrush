{{--
    An order's full customer-facing details: number/date/payment, progress
    steps (or a cancellation notice), items with dimensions, totals and
    address. Shared by the guest tracking page and the customer dashboard.
    @param \App\Models\Order $order
--}}
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
                <div>
                    <span>{{ $item->name_snapshot }} × {{ $item->quantity }}</span>
                    @include('orders.partials.customization', ['customization' => $item->customization])
                </div>
                <span class="shrink-0" dir="ltr">{{ \App\Support\Money::toman($item->line_total_rial) }}</span>
            </li>
        @endforeach
    </ul>
    <div class="mt-3 border-t border-neutral-100 pt-3">
        @include('orders.partials.totals', ['subtotalRial' => $order->subtotal_rial, 'shipping' => $order->shippingQuote(), 'priceNeedsConfirmation' => $order->requires_price_confirmation])
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
