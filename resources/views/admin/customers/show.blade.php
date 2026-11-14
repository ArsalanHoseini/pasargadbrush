@extends('admin.layout')

@section('title', $customer->name)

@php
$orderStatusLabels = \App\Models\Order::STATUS_LABELS;
$quoteStatusLabels = ['new' => 'جدید', 'contacted' => 'تماس گرفته شد', 'quoted' => 'قیمت ارسال شد', 'closed' => 'بسته شده'];
@endphp

@section('content')
<x-admin.page-header :title="$customer->name" :description="($customer->company_name ? $customer->company_name.' — ' : '').'عضو از '.persian_date($customer->created_at, \App\Support\PersianDate::DATE)">
    <x-slot:actions>
        <x-admin.button variant="secondary" icon="chat-bubble" :href="route('admin.conversations.create', ['customer' => $customer->id])">ارسال پیام</x-admin.button>
        <x-admin.button icon="plus" :href="route('admin.orders.create')">ثبت سفارش دستی</x-admin.button>
    </x-slot:actions>
</x-admin.page-header>

<div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
    @foreach ([
        ['label' => 'سفارش‌ها', 'value' => $stats['order_count'], 'hint' => 'بدون احتساب لغوشده‌ها'],
        ['label' => 'مجموع خرید', 'value' => \App\Support\Money::toman($stats['total_rial']), 'hint' => null],
        ['label' => 'میانگین هر سفارش', 'value' => \App\Support\Money::toman($stats['average_rial']), 'hint' => null],
        ['label' => 'آخرین سفارش', 'value' => $stats['last_order_at'] ? persian_date($stats['last_order_at'], \App\Support\PersianDate::DATE) : '—', 'hint' => $stats['unpaid_count'] ? $stats['unpaid_count'].' سفارش پرداخت‌نشده' : null],
    ] as $card)
        <div class="rounded-xl border border-neutral-200 bg-white p-4">
            <p class="text-xs text-neutral-500">{{ $card['label'] }}</p>
            <p class="mt-1 text-lg font-bold" @if (str_contains((string) $card['value'], 'تومان')) dir="ltr" style="text-align: right" @endif>{{ $card['value'] }}</p>
            @if ($card['hint'])
                <p class="mt-0.5 text-xs text-neutral-400">{{ $card['hint'] }}</p>
            @endif
        </div>
    @endforeach
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6">
        <div class="rounded-xl border border-neutral-200 bg-white p-6">
            <h2 class="mb-4 text-sm font-medium text-neutral-500">اطلاعات تماس</h2>
            <div class="space-y-3 text-sm">
                <div>
                    <p class="text-xs text-neutral-500">موبایل</p>
                    <a href="tel:{{ $customer->phone }}" class="font-medium hover:text-brand-accent-dark" dir="ltr">{{ $customer->phone }}</a>
                </div>
                @if ($customer->email)
                    <div>
                        <p class="text-xs text-neutral-500">ایمیل</p>
                        <a href="mailto:{{ $customer->email }}" class="font-medium hover:text-brand-accent-dark" dir="ltr">{{ $customer->email }}</a>
                    </div>
                @endif
            </div>

            @if ($addresses->isNotEmpty())
                <h3 class="mb-2 mt-6 text-sm font-medium text-neutral-500">آدرس‌ها</h3>
                <ul class="space-y-2 text-sm">
                    @foreach ($addresses as $address)
                        <li class="rounded-lg border border-neutral-100 p-3">
                            {{ $address->province }}، {{ $address->city }} — {{ $address->address_line }}
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="rounded-xl border border-neutral-200 bg-white p-6">
            <h2 class="mb-4 text-sm font-medium text-neutral-500">درخواست‌های استعلام</h2>
            @if ($quoteRequests->isEmpty())
                <p class="text-sm text-neutral-500">درخواستی ثبت نشده است.</p>
            @else
                <ul class="divide-y divide-neutral-100 text-sm">
                    @foreach ($quoteRequests as $quoteRequest)
                        <li class="flex items-center justify-between gap-2 py-2">
                            <a href="{{ route('admin.quote-requests.edit', $quoteRequest) }}" class="hover:text-brand-accent-dark">{{ $quoteRequest->product?->name ?? 'استعلام عمومی' }}</a>
                            <span class="shrink-0 text-xs text-neutral-500">{{ $quoteStatusLabels[$quoteRequest->status] ?? $quoteRequest->status }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-neutral-200 bg-white lg:col-span-2">
        <h2 class="px-6 pt-6 text-sm font-medium text-neutral-500">سفارش‌ها</h2>
        @if ($orders->isEmpty())
            <p class="px-6 py-6 text-sm text-neutral-500">سفارشی ثبت نشده است.</p>
        @else
            <table class="mt-3 w-full text-sm">
                <thead class="border-y border-neutral-200 bg-neutral-50 text-right text-xs text-neutral-500">
                    <tr>
                        <th class="px-4 py-2 font-medium">شماره</th>
                        <th class="px-4 py-2 font-medium">تاریخ</th>
                        <th class="px-4 py-2 font-medium">وضعیت</th>
                        <th class="px-4 py-2 font-medium">پرداخت</th>
                        <th class="px-4 py-2 font-medium">مبلغ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @foreach ($orders as $order)
                        <tr @class(['hover:bg-neutral-50', 'text-neutral-400' => $order->status === 'cancelled'])>
                            <td class="px-4 py-2"><a href="{{ route('admin.orders.edit', $order) }}" class="font-medium hover:text-brand-accent-dark" dir="ltr">{{ $order->order_number }}</a></td>
                            <td class="whitespace-nowrap px-4 py-2 text-neutral-500">{{ persian_date($order->placed_at, \App\Support\PersianDate::COMPACT_DATE) }}</td>
                            <td class="px-4 py-2">{{ $orderStatusLabels[$order->status] ?? $order->status }}</td>
                            <td class="px-4 py-2">{{ \App\Models\Order::PAYMENT_STATUS_LABELS[$order->payment_status] ?? $order->payment_status }}</td>
                            <td class="whitespace-nowrap px-4 py-2" dir="ltr">{{ \App\Support\Money::toman($order->total_rial) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
