@extends('account.layout')

@section('title', 'حساب کاربری')

@section('account-content')
<h1 class="mb-6 text-xl font-bold text-neutral-900">سلام {{ $customer->name }}</h1>

<div class="mb-6 grid gap-3 sm:grid-cols-3">
    <a href="{{ route('account.orders.index') }}" class="rounded-lg border border-neutral-200 bg-white p-4 hover:border-neutral-400">
        <p class="text-xs text-neutral-500">سفارش‌های در جریان</p>
        <p class="mt-1 text-2xl font-bold">{{ $openOrderCount }}</p>
    </a>
    <a href="{{ route('account.quote-requests.index') }}" class="rounded-lg border border-neutral-200 bg-white p-4 hover:border-neutral-400">
        <p class="text-xs text-neutral-500">استعلام‌های قیمت</p>
        <p class="mt-1 text-sm font-medium">مشاهده</p>
    </a>
    <a href="{{ route('account.addresses.index') }}" class="rounded-lg border border-neutral-200 bg-white p-4 hover:border-neutral-400">
        <p class="text-xs text-neutral-500">آدرس‌ها</p>
        <p class="mt-1 text-sm font-medium">مدیریت دفترچه آدرس</p>
    </a>
</div>

<div class="mb-6 rounded-lg border border-neutral-200 bg-white p-5">
    <div class="mb-3 flex items-center justify-between">
        <h2 class="text-sm font-bold">آخرین سفارش‌ها</h2>
        <a href="{{ route('account.orders.index') }}" class="text-xs text-brand-accent-dark hover:underline">همه سفارش‌ها</a>
    </div>
    @include('account.orders.partials.table', ['orders' => $recentOrders])
</div>

<div class="rounded-lg border border-neutral-200 bg-white p-5">
    <div class="mb-3 flex items-center justify-between">
        <h2 class="text-sm font-bold">آخرین استعلام‌ها</h2>
        <a href="{{ route('account.quote-requests.index') }}" class="text-xs text-brand-accent-dark hover:underline">همه استعلام‌ها</a>
    </div>
    @forelse ($recentQuoteRequests as $quoteRequest)
        <div class="flex justify-between border-b border-neutral-100 py-2 text-sm last:border-0">
            <span>{{ $quoteRequest->product?->name ?? 'درخواست عمومی' }}</span>
            <span class="text-xs text-neutral-500">{{ \App\Http\Controllers\Account\QuoteRequestController::STATUS_LABELS[$quoteRequest->status] ?? $quoteRequest->status }}</span>
        </div>
    @empty
        <p class="text-sm text-neutral-500">هنوز استعلام قیمتی نفرستاده‌اید.</p>
    @endforelse
</div>
@endsection
