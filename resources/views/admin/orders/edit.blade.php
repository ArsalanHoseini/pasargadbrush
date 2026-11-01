@extends('admin.layout')

@section('title', 'سفارش '.$order->order_number)

@section('content')
<h1 class="mb-6 text-lg font-semibold" dir="ltr">{{ $order->order_number }}</h1>

@if ($errors->any())
    <div class="mb-6 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<div class="grid gap-6 lg:grid-cols-2">
    <div class="space-y-6">
        <div class="rounded-lg border border-neutral-200 bg-white p-6">
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
            <div class="rounded-lg border border-neutral-200 bg-white p-6">
                <h2 class="mb-4 text-sm font-medium text-neutral-500">آدرس ارسال</h2>
                <p class="text-sm">{{ $order->shippingAddress->province }}، {{ $order->shippingAddress->city }}</p>
                <p class="text-sm text-neutral-600">{{ $order->shippingAddress->address_line }}</p>
                @if ($order->shippingAddress->postal_code)
                    <p class="text-sm text-neutral-500" dir="ltr">کد پستی: {{ $order->shippingAddress->postal_code }}</p>
                @endif
            </div>
        @endif

        <div class="rounded-lg border border-neutral-200 bg-white p-6">
            <h2 class="mb-4 text-sm font-medium text-neutral-500">اقلام سفارش</h2>
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

    <div class="rounded-lg border border-neutral-200 bg-white p-6">
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
                <label class="mb-1 block text-sm font-medium">وضعیت سفارش</label>
                <select name="status" class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
                    @foreach (\App\Models\Order::STATUS_LABELS as $value => $label)
                        <option value="{{ $value }}" @selected($order->status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            @if ($shipping->method !== \App\Services\Checkout\ShippingMethod::CashOnDelivery)
                <div>
                    <label class="mb-1 block text-sm font-medium">هزینه ارسال (تومان)</label>
                    <input name="shipping_cost_toman" type="number" min="0" dir="ltr"
                           value="{{ old('shipping_cost_toman', $order->shipping_cost_rial > 0 ? intdiv($order->shipping_cost_rial, 10) : '') }}"
                           class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
                    <p class="mt-1 text-xs text-neutral-500">جمع کل سفارش بر اساس این مبلغ دوباره محاسبه می‌شود.</p>
                </div>
            @endif

            <div>
                <label class="mb-1 block text-sm font-medium">کد رهگیری مرسوله</label>
                <input name="shipment_tracking_code" value="{{ old('shipment_tracking_code', $order->shipment_tracking_code) }}" dir="ltr"
                       class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-neutral-500">
                    کد رهگیری پست یا باربری — در صفحه‌ی پیگیری سفارش به مشتری نمایش داده می‌شود.
                </p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">وضعیت پرداخت</label>
                <select name="payment_status" class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
                    @foreach (\App\Models\Order::PAYMENT_STATUS_LABELS as $value => $label)
                        <option value="{{ $value }}" @selected($order->payment_status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-neutral-500">
                    پرداخت آنلاین (زرین‌پال) هنوز فعال نشده — این وضعیت را پس از دریافت پرداخت به‌صورت دستی به «پرداخت‌شده» تغییر دهید.
                </p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">یادداشت داخلی</label>
                <textarea name="notes" rows="4" class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">{{ old('notes', $order->notes) }}</textarea>
            </div>

            <button type="submit" class="rounded bg-neutral-900 px-4 py-2 text-sm text-white hover:bg-neutral-700">
                ذخیره
            </button>
        </form>
    </div>
</div>
@endsection
