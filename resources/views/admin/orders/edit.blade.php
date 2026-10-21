@extends('admin.layout')

@section('title', 'سفارش '.$order->order_number)

@section('content')
<h1 class="mb-6 text-lg font-semibold" dir="ltr">{{ $order->order_number }}</h1>

<div class="grid gap-6 lg:grid-cols-2">
    <div class="space-y-6">
        <div class="rounded-lg border border-neutral-200 bg-white p-6">
            <h2 class="mb-4 text-sm font-medium text-neutral-500">مشتری</h2>
            <dl class="space-y-2 text-sm">
                <div><dt class="text-neutral-500">نام</dt><dd class="font-medium">{{ $order->customer?->name }}</dd></div>
                <div><dt class="text-neutral-500">تلفن</dt><dd class="font-medium" dir="ltr">{{ $order->customer?->phone }}</dd></div>
                @if ($order->customer?->email)
                    <div><dt class="text-neutral-500">ایمیل</dt><dd class="font-medium" dir="ltr">{{ $order->customer->email }}</dd></div>
                @endif
            </dl>
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
                    <li class="flex justify-between py-2">
                        <span>{{ $item->name_snapshot }} × {{ $item->quantity }}</span>
                        <span dir="ltr">{{ \App\Support\Money::toman($item->line_total_rial) }}</span>
                    </li>
                @endforeach
            </ul>
            <div class="mt-3 space-y-1 border-t border-neutral-100 pt-3 text-sm">
                <div class="flex justify-between"><span>جمع اقلام</span><span dir="ltr">{{ \App\Support\Money::toman($order->subtotal_rial) }}</span></div>
                <div class="flex justify-between"><span>هزینه ارسال</span><span dir="ltr">{{ \App\Support\Money::toman($order->shipping_cost_rial) }}</span></div>
                <div class="flex justify-between font-bold"><span>جمع کل</span><span dir="ltr">{{ \App\Support\Money::toman($order->total_rial) }}</span></div>
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-neutral-200 bg-white p-6">
        <h2 class="mb-4 text-sm font-medium text-neutral-500">مدیریت سفارش</h2>
        <form method="POST" action="{{ route('admin.orders.update', $order) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="mb-1 block text-sm font-medium">وضعیت سفارش</label>
                <select name="status" class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
                    @foreach (['pending' => 'در انتظار', 'confirmed' => 'تأییدشده', 'processing' => 'در حال پردازش', 'shipped' => 'ارسال‌شده', 'completed' => 'تکمیل‌شده', 'cancelled' => 'لغوشده'] as $value => $label)
                        <option value="{{ $value }}" @selected($order->status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">وضعیت پرداخت</label>
                <select name="payment_status" class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
                    @foreach (['unpaid' => 'پرداخت‌نشده', 'paid' => 'پرداخت‌شده', 'failed' => 'ناموفق', 'refunded' => 'بازگشت‌شده'] as $value => $label)
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
