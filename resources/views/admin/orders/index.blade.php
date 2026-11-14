@extends('admin.layout')

@section('title', 'سفارش‌ها')

@php
$statusLabels = \App\Models\Order::STATUS_LABELS;
@endphp

@section('content')
<x-admin.page-header title="سفارش‌ها" description="سفارش‌های سایت و سفارش‌های ثبت‌شده توسط کارشناسان">
    <x-slot:actions>
        <x-admin.button :href="route('admin.orders.create')" icon="plus">ثبت سفارش دستی</x-admin.button>
    </x-slot:actions>
</x-admin.page-header>

<div class="mb-4 flex flex-wrap gap-1 border-b border-neutral-200 text-sm">
    <a href="{{ route('admin.orders.index') }}"
       @class(['-mb-px border-b-2 px-3 py-2', 'border-brand-accent font-medium text-neutral-900' => ! $status, 'border-transparent text-neutral-500 hover:text-neutral-900' => $status])>
        همه <span class="text-xs text-neutral-400">{{ $statusCounts->sum() }}</span>
    </a>
    @foreach ($statusLabels as $value => $label)
        <a href="{{ route('admin.orders.index', ['status' => $value]) }}"
           @class(['-mb-px border-b-2 px-3 py-2', 'border-brand-accent font-medium text-neutral-900' => $status === $value, 'border-transparent text-neutral-500 hover:text-neutral-900' => $status !== $value])>
            {{ $label }} <span class="text-xs text-neutral-400">{{ $statusCounts[$value] ?? 0 }}</span>
        </a>
    @endforeach
</div>

<x-admin.filter-bar :action="route('admin.orders.index')" :keep="['status']" placeholder="شماره سفارش، نام، موبایل یا کد رهگیری…">
    <x-admin.filter-select name="payment_status" label="پرداخت" :options="\App\Models\Order::PAYMENT_STATUS_LABELS" />
    <x-admin.filter-select name="source" label="منبع" :options="[\App\Models\Order::SOURCE_WEBSITE => 'سایت', \App\Models\Order::SOURCE_MANUAL => 'ثبت دستی']" />
    <x-admin.filter-select name="price_confirmation" label="تأیید قیمت" :options="['1' => 'منتظر تأیید قیمت']" />
</x-admin.filter-bar>

@if ($orders->isEmpty())
    <x-admin.empty-state icon="shopping-bag">سفارشی با این مشخصات پیدا نشد.</x-admin.empty-state>
@else
    <div class="overflow-x-auto rounded-xl border border-neutral-200 bg-white">
        <table class="w-full text-sm">
            <thead class="border-b border-neutral-200 bg-neutral-50 text-right">
                <tr>
                    <th class="px-4 py-3 font-medium">شماره سفارش</th>
                    <th class="px-4 py-3 font-medium">مشتری</th>
                    <th class="px-4 py-3 font-medium">مبلغ کل</th>
                    <th class="px-4 py-3 font-medium">وضعیت</th>
                    <th class="px-4 py-3 font-medium">پرداخت</th>
                    <th class="px-4 py-3 font-medium">تاریخ</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @foreach ($orders as $order)
                    <tr class="hover:bg-neutral-50">
                        <td class="px-4 py-3 font-medium" dir="ltr"><a href="{{ route('admin.orders.edit', $order) }}" class="hover:text-brand-accent-dark">{{ $order->order_number }}</a></td>
                        <td class="px-4 py-3">{{ ($order->customer_name ?? $order->customer?->name) ?? '—' }}</td>
                        <td class="px-4 py-3" dir="ltr">{{ \App\Support\Money::toman($order->total_rial) }}</td>
                        <td class="px-4 py-3">
                            {{ $statusLabels[$order->status] }}
                            @if ($order->source === \App\Models\Order::SOURCE_MANUAL)
                                <span class="mr-1 rounded bg-blue-50 px-1.5 py-0.5 text-xs text-blue-700">ثبت دستی</span>
                            @endif
                            @if ($order->requires_price_confirmation)
                                <span class="mr-1 rounded bg-amber-100 px-1.5 py-0.5 text-xs text-amber-800">نیاز به تأیید قیمت</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($order->payment_status === 'paid')
                                <span class="rounded bg-green-50 px-2 py-0.5 text-xs text-green-700">پرداخت‌شده</span>
                            @else
                                <span class="rounded bg-amber-50 px-2 py-0.5 text-xs text-amber-700">پرداخت‌نشده</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-neutral-500">{{ persian_date($order->placed_at, \App\Support\PersianDate::COMPACT) }}</td>
                        <td class="px-4 py-3 text-left">
                            <a href="{{ route('admin.orders.edit', $order) }}" class="text-neutral-600 hover:text-neutral-900">مشاهده</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $orders->links() }}</div>
@endif
@endsection
