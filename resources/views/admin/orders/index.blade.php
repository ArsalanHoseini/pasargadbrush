@extends('admin.layout')

@section('title', 'سفارش‌ها')

@php
$statusLabels = ['pending' => 'در انتظار', 'confirmed' => 'تأییدشده', 'processing' => 'در حال پردازش', 'shipped' => 'ارسال‌شده', 'completed' => 'تکمیل‌شده', 'cancelled' => 'لغوشده'];
@endphp

@section('content')
<h1 class="mb-6 text-lg font-semibold">سفارش‌ها</h1>

<div class="mb-6 flex flex-wrap gap-2 text-sm">
    <a href="{{ route('admin.orders.index') }}"
       class="rounded px-3 py-1.5 {{ ! $status ? 'bg-neutral-900 text-white' : 'border border-neutral-300' }}">همه</a>
    @foreach ($statusLabels as $value => $label)
        <a href="{{ route('admin.orders.index', ['status' => $value]) }}"
           class="rounded px-3 py-1.5 {{ $status === $value ? 'bg-neutral-900 text-white' : 'border border-neutral-300' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

@if ($orders->isEmpty())
    <p class="text-sm text-neutral-500">سفارشی ثبت نشده است.</p>
@else
    <div class="overflow-x-auto rounded-lg border border-neutral-200 bg-white">
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
                    <tr>
                        <td class="px-4 py-3" dir="ltr">{{ $order->order_number }}</td>
                        <td class="px-4 py-3">{{ ($order->customer_name ?? $order->customer?->name) ?? '—' }}</td>
                        <td class="px-4 py-3" dir="ltr">{{ \App\Support\Money::toman($order->total_rial) }}</td>
                        <td class="px-4 py-3">{{ $statusLabels[$order->status] }}</td>
                        <td class="px-4 py-3">
                            @if ($order->payment_status === 'paid')
                                <span class="rounded bg-green-50 px-2 py-0.5 text-xs text-green-700">پرداخت‌شده</span>
                            @else
                                <span class="rounded bg-amber-50 px-2 py-0.5 text-xs text-amber-700">پرداخت‌نشده</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-neutral-500">{{ $order->placed_at?->format('Y-m-d H:i') }}</td>
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
