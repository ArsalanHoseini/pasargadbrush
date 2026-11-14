@extends('admin.layout')

@section('title', 'جستجو')

@php
    $resultCount = $results ? collect($results)->sum(fn ($group) => $group->count()) : 0;
@endphp

@section('content')
<x-admin.page-header title="جستجو"
    :description="$term === '' ? 'شماره سفارش، نام یا موبایل مشتری، نام یا SKU محصول، کد رهگیری…' : $resultCount.' نتیجه برای «'.$term.'»'" />

@if ($term !== '' && $resultCount === 0)
    <div class="flex flex-col items-center gap-2 rounded-xl border border-dashed border-neutral-300 bg-white py-16 text-center text-sm text-neutral-500">
        <x-admin.icon name="search" class="size-8 text-neutral-300" />
        نتیجه‌ای برای «{{ $term }}» پیدا نشد.
    </div>
@elseif ($results)
    <div class="grid gap-6 lg:grid-cols-2">
        @if ($results['orders']->isNotEmpty())
            <section class="rounded-xl border border-neutral-200 bg-white">
                <h2 class="flex items-center gap-2 border-b border-neutral-100 px-5 py-3 font-semibold"><x-admin.icon name="shopping-bag" class="size-4 text-neutral-400" /> سفارش‌ها</h2>
                <ul class="divide-y divide-neutral-100 text-sm">
                    @foreach ($results['orders'] as $order)
                        <li>
                            <a href="{{ route('admin.orders.edit', $order) }}" class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-neutral-50">
                                <span><span dir="ltr" class="font-medium">{{ $order->order_number }}</span> — {{ $order->customer_name ?? '—' }}</span>
                                <span class="shrink-0 text-xs text-neutral-500">{{ \App\Models\Order::STATUS_LABELS[$order->status] ?? $order->status }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($results['products']->isNotEmpty())
            <section class="rounded-xl border border-neutral-200 bg-white">
                <h2 class="flex items-center gap-2 border-b border-neutral-100 px-5 py-3 font-semibold"><x-admin.icon name="cube" class="size-4 text-neutral-400" /> محصولات</h2>
                <ul class="divide-y divide-neutral-100 text-sm">
                    @foreach ($results['products'] as $product)
                        <li>
                            <a href="{{ route('admin.products.edit', $product) }}" class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-neutral-50">
                                <span>{{ $product->name }} <span class="text-xs text-neutral-400" dir="ltr">{{ $product->sku }}</span></span>
                                <span class="shrink-0 text-xs text-neutral-500">{{ $product->primaryCategory?->name }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($results['customers']->isNotEmpty())
            <section class="rounded-xl border border-neutral-200 bg-white">
                <h2 class="flex items-center gap-2 border-b border-neutral-100 px-5 py-3 font-semibold"><x-admin.icon name="users" class="size-4 text-neutral-400" /> مشتریان</h2>
                <ul class="divide-y divide-neutral-100 text-sm">
                    @foreach ($results['customers'] as $customer)
                        <li>
                            <a href="{{ route('admin.customers.show', $customer) }}" class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-neutral-50">
                                <span>{{ $customer->name }} @if ($customer->company_name)<span class="text-neutral-500">— {{ $customer->company_name }}</span>@endif</span>
                                <span class="shrink-0 text-xs text-neutral-500" dir="ltr">{{ $customer->phone }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($results['quoteRequests']->isNotEmpty())
            <section class="rounded-xl border border-neutral-200 bg-white">
                <h2 class="flex items-center gap-2 border-b border-neutral-100 px-5 py-3 font-semibold"><x-admin.icon name="inbox" class="size-4 text-neutral-400" /> درخواست‌های استعلام</h2>
                <ul class="divide-y divide-neutral-100 text-sm">
                    @foreach ($results['quoteRequests'] as $quoteRequest)
                        <li>
                            <a href="{{ route('admin.quote-requests.edit', $quoteRequest) }}" class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-neutral-50">
                                <span>{{ $quoteRequest->name }} — <span class="text-neutral-500">{{ $quoteRequest->product?->name ?? 'عمومی' }}</span></span>
                                <span class="shrink-0 text-xs text-neutral-500" dir="ltr">{{ $quoteRequest->phone }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
@endif
@endsection
