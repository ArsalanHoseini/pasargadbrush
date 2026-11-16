@extends('admin.layout')

@section('title', 'سفارش '.$order->order_number)

@section('content')
@php
    $statusLabel = \App\Models\Order::STATUS_LABELS[$order->status] ?? $order->status;
    $origin = $order->source === \App\Models\Order::SOURCE_MANUAL
        ? 'ثبت دستی'.($order->createdBy ? ' توسط '.$order->createdBy->name : '')
        : 'ثبت‌شده توسط مشتری در سایت';
@endphp
<x-admin.page-header :title="'سفارش '.$order->order_number" :description="$statusLabel.' — '.$origin.' — '.persian_date($order->placed_at)">
    <x-slot:actions>
        @if ($order->customer)
            <x-admin.button variant="secondary" icon="chat-bubble"
                :href="$order->conversation ? route('admin.conversations.show', $order->conversation) : route('admin.conversations.create', ['order' => $order->id])">
                {{ $order->conversation ? 'گفتگو با مشتری' : 'پیام به مشتری' }}
            </x-admin.button>
        @endif
        <x-admin.button variant="secondary" icon="printer" :href="route('admin.orders.print', [$order, 'invoice'])" target="_blank">فاکتور</x-admin.button>
        <x-admin.button variant="secondary" icon="truck" :href="route('admin.orders.print', [$order, 'label'])" target="_blank">برچسب ارسال</x-admin.button>
    </x-slot:actions>
</x-admin.page-header>

<x-admin.form-errors />

<div class="grid gap-6 lg:grid-cols-2">
    <div class="space-y-6">
        <div class="admin-card p-6">
            <h2 class="mb-4 text-sm font-medium text-neutral-500">مشتری</h2>
            <dl class="space-y-2 text-sm">
                <div><dt class="text-neutral-500">نام</dt><dd class="font-medium">{{ $order->customer_name ?? $order->customer?->name }}</dd></div>
                <div><dt class="text-neutral-500">تلفن</dt><dd class="font-medium" dir="ltr">{{ $order->customer_phone ?? $order->customer?->phone }}</dd></div>
                @if ($order->customer_email ?? $order->customer?->email)
                    <div><dt class="text-neutral-500">ایمیل</dt><dd class="font-medium" dir="ltr">{{ $order->customer_email ?? $order->customer?->email }}</dd></div>
                @endif
            </dl>

            @if ($order->customer && $order->customer_name && $order->customer_name !== $order->customer->name)
                <p class="mt-4 rounded border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
                    نام واردشده در این سفارش با پرونده‌ی مشتری این شماره
                    (<a href="{{ route('admin.customers.show', $order->customer) }}" class="font-medium underline">{{ $order->customer->name }}</a>)
                    یکی نیست — قبل از ارسال، با تماس تلفنی تأیید کنید.
                </p>
            @endif
        </div>

        @if ($order->shippingAddress)
            <div class="admin-card p-6">
                <h2 class="mb-4 text-sm font-medium text-neutral-500">آدرس ارسال</h2>
                <p class="text-sm">{{ $order->shippingAddress->province }}، {{ $order->shippingAddress->city }}</p>
                <p class="text-sm text-neutral-600">{{ $order->shippingAddress->address_line }}</p>
                @if ($order->shippingAddress->postal_code)
                    <p class="text-sm text-neutral-500" dir="ltr">کد پستی: {{ $order->shippingAddress->postal_code }}</p>
                @endif
            </div>
        @endif

        <div class="admin-card p-6">
            <h2 class="mb-4 text-sm font-medium text-neutral-500">اقلام سفارش</h2>
            <ul class="divide-y divide-neutral-100 text-sm">
                @foreach ($order->items as $item)
                    <li class="flex justify-between gap-4 py-2">
                        <div>
                            <span>{{ $item->name_snapshot }} × {{ $item->quantity }}</span>
                            @include('orders.partials.customization', ['customization' => $item->customization])
                        </div>
                        <span class="shrink-0">@if ($item->isAwaitingPrice($order))<span class="text-xs text-neutral-500">در انتظار تعیین قیمت</span>@else<span dir="ltr">{{ \App\Support\Money::toman($item->line_total_rial) }}</span>@endif</span>
                    </li>
                @endforeach
            </ul>
            @if ($order->requires_price_confirmation)
                <p class="mt-3 rounded bg-amber-50 p-2 text-xs text-amber-800">
                    این سفارش اقلام با ابعاد سفارشی دارد و قیمت‌ها هنوز «قیمت پایه» هستند — ابعاد را بررسی کنید و قیمت نهایی را در فرم «مدیریت سفارش» تأیید کنید.
                </p>
            @endif
            <div class="mt-3 space-y-1 border-t border-neutral-100 pt-3 text-sm">
                <div class="flex justify-between"><span>جمع اقلام</span><span dir="ltr">{{ \App\Support\Money::toman($order->subtotal_rial) }}</span></div>
                <div class="flex justify-between"><span>روش ارسال</span><span>{{ $shipping->method->label() }}</span></div>
                <div class="flex justify-between"><span>هزینه ارسال</span><span>{{ $shipping->description() }}</span></div>
                @if ($shipping->isTotalKnown())
                    <div class="flex justify-between font-bold"><span>جمع کل</span><span dir="ltr">{{ \App\Support\Money::toman($order->total_rial) }}</span></div>
                @else
                    <p class="rounded bg-amber-50 p-2 text-xs text-amber-800">
                        هزینه ارسال این سفارش هنوز وارد نشده — پس از هماهنگی با مشتری، آن را در فرم «مدیریت سفارش» وارد کنید.
                    </p>
                @endif
            </div>
        </div>
    </div>

    <div class="admin-card p-6">
        <h2 class="mb-4 text-sm font-medium text-neutral-500">مدیریت سفارش</h2>
        <form method="POST" action="{{ route('admin.orders.update', $order) }}" class="space-y-4">
            @csrf
            @method('PUT')

            @if ($order->requires_price_confirmation)
                <fieldset class="rounded border border-amber-200 bg-amber-50/50 p-3">
                    <legend class="px-1 text-sm font-medium">تأیید قیمت نهایی</legend>
                    <div class="space-y-2">
                        @foreach ($order->items as $item)
                            <div class="flex items-center justify-between gap-3">
                                <label for="item-price-{{ $item->id }}" class="text-xs">{{ $item->name_snapshot }} × {{ $item->quantity }} — قیمت واحد (تومان)</label>
                                <input id="item-price-{{ $item->id }}" name="item_prices[{{ $item->id }}]" type="number" min="0" dir="ltr"
                                       value="{{ old('item_prices.'.$item->id, intdiv($item->unit_price_rial, 10)) }}"
                                       class="w-36 rounded border border-neutral-300 px-2 py-1 text-sm">
                            </div>
                        @endforeach
                    </div>
                    <label class="mt-3 flex items-center gap-2 text-sm">
                        <input type="checkbox" name="price_confirmed" value="1">
                        قیمت نهایی تأیید شد (برچسب «نیاز به تأیید قیمت» برداشته می‌شود)
                    </label>
                </fieldset>
            @endif

            <div>
                <label class="admin-label">وضعیت سفارش</label>
                <select name="status" class="admin-input">
                    @foreach (\App\Models\Order::STATUS_LABELS as $value => $label)
                        <option value="{{ $value }}" @selected($order->status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            @if ($shipping->method !== \App\Services\Checkout\ShippingMethod::CashOnDelivery)
                <div>
                    <label class="admin-label">هزینه ارسال (تومان)</label>
                    <input name="shipping_cost_toman" type="number" min="0" dir="ltr"
                           value="{{ old('shipping_cost_toman', $order->shipping_cost_rial > 0 ? intdiv($order->shipping_cost_rial, 10) : '') }}"
                           class="admin-input">
                    <p class="mt-1 text-xs text-neutral-500">جمع کل سفارش بر اساس این مبلغ دوباره محاسبه می‌شود.</p>
                </div>
            @endif

            <div>
                <label class="admin-label">کد رهگیری مرسوله</label>
                <input name="shipment_tracking_code" value="{{ old('shipment_tracking_code', $order->shipment_tracking_code) }}" dir="ltr"
                       class="admin-input">
                <p class="mt-1 text-xs text-neutral-500">
                    کد رهگیری پست یا باربری — در صفحه‌ی پیگیری سفارش به مشتری نمایش داده می‌شود.
                </p>
            </div>

            <div>
                <label class="admin-label">وضعیت پرداخت</label>
                <select name="payment_status" class="admin-input">
                    @foreach (\App\Models\Order::PAYMENT_STATUS_LABELS as $value => $label)
                        <option value="{{ $value }}" @selected($order->payment_status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-neutral-500">
                    پرداخت آنلاین (زرین‌پال) هنوز فعال نشده — این وضعیت را پس از دریافت پرداخت به‌صورت دستی به «پرداخت‌شده» تغییر دهید.
                </p>
            </div>

            <div>
                <label class="admin-label">توضیحات سفارش</label>
                <textarea name="notes" rows="4" class="admin-input">{{ old('notes', $order->notes) }}</textarea>
            </div>

            <label class="flex items-start gap-2 rounded border border-neutral-200 bg-neutral-50 p-3 text-sm">
                <input type="hidden" name="notify_customer" value="0">
                <input type="checkbox" name="notify_customer" value="1" class="mt-1" @checked(old('notify_customer', '1') === '1')>
                <span>
                    اطلاع‌رسانی به مشتری
                    <span class="block text-xs text-neutral-500">
                        اگر وضعیت سفارش تغییر کند یا قیمت نهایی تأیید شود، به مشتری پیامک
                        {{ $order->customer_email ? 'و ایمیل' : '' }} ارسال می‌شود. برای اصلاحات جزئی، تیک را بردارید.
                    </span>
                </span>
            </label>

            <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-accent-dark px-5 py-2 text-sm font-medium text-white hover:bg-brand-accent-darker">
                ذخیره
            </button>
        </form>
    </div>
</div>

<section id="timeline" class="mt-6 rounded-xl border border-neutral-200 bg-white p-6">
    <h2 class="mb-4 flex items-center gap-2 text-sm font-medium text-neutral-500"><x-admin.icon name="clock" class="size-4" /> تاریخچه و یادداشت‌های داخلی</h2>

    <form method="POST" action="{{ route('admin.orders.notes.store', $order) }}" class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-start">
        @csrf
        <textarea name="body" rows="2" required maxlength="2000" placeholder="یادداشتی برای همکاران بنویسید (به مشتری نمایش داده نمی‌شود)…" aria-label="یادداشت داخلی"
                  class="w-full flex-1 rounded-lg border border-neutral-300 px-3 py-2 text-sm focus:border-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-accent/20">{{ old('body') }}</textarea>
        <x-admin.button icon="plus">ثبت یادداشت</x-admin.button>
    </form>

    <ol class="relative space-y-5 border-r border-neutral-200 pr-5">
        @foreach ($timeline as $entry)
            <li class="relative">
                <span @class([
                    'absolute -right-[1.65rem] top-1 size-3 rounded-full ring-4 ring-white',
                    'bg-amber-400' => $entry['type'] === 'note',
                    'bg-brand-accent' => $entry['type'] === 'change',
                    'bg-green-500' => $entry['type'] === 'created',
                ])></span>
                <p class="text-sm font-medium">
                    {{ $entry['title'] }}
                    <span class="font-normal text-neutral-500">— {{ $entry['user'] ?? 'سیستم' }}، {{ persian_date($entry['at'], \App\Support\PersianDate::COMPACT) }}</span>
                </p>
                @if ($entry['type'] === 'note')
                    <p class="mt-1 whitespace-pre-line rounded-lg bg-amber-50 p-3 text-sm text-neutral-800">{{ $entry['details'][0] }}</p>
                @elseif ($entry['details'] !== [])
                    <ul class="mt-1 space-y-0.5 text-sm text-neutral-600">
                        @foreach ($entry['details'] as $detail)
                            <li>{{ $detail }}</li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
    </ol>
</section>
@endsection
