@extends('layouts.public')

@section('robots', 'noindex, nofollow')

@php
    $accountNav = [
        ['route' => 'account.dashboard', 'match' => 'account.dashboard', 'label' => 'پیشخوان'],
        ['route' => 'account.orders.index', 'match' => 'account.orders.*', 'label' => 'سفارش‌های من'],
        ['route' => 'account.quote-requests.index', 'match' => 'account.quote-requests.*', 'label' => 'استعلام‌های قیمت'],
        ['route' => 'account.conversations.index', 'match' => 'account.conversations.*', 'label' => 'پیام‌ها', 'badge' => auth('customer')->user()->conversations()->where('customer_unread', true)->count()],
        ['route' => 'account.addresses.index', 'match' => 'account.addresses.*', 'label' => 'آدرس‌ها'],
        ['route' => 'account.profile.edit', 'match' => 'account.profile.*', 'label' => 'حساب کاربری'],
    ];
@endphp

@section('content')
<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
    <div class="grid gap-6 lg:grid-cols-[220px_1fr]">
        <aside class="h-fit rounded-lg border border-neutral-200 bg-white p-4">
            <p class="mb-1 truncate text-sm font-bold text-neutral-900">{{ auth('customer')->user()->name ?: 'حساب من' }}</p>
            <p class="mb-4 text-xs text-neutral-500" dir="ltr">{{ auth('customer')->user()->phone }}</p>
            <nav class="flex gap-1 overflow-x-auto text-sm lg:flex-col" aria-label="حساب کاربری">
                @foreach ($accountNav as $item)
                    <a href="{{ route($item['route']) }}"
                       @class([
                           'shrink-0 rounded px-3 py-2',
                           'bg-neutral-900 text-white' => request()->routeIs($item['match']),
                           'text-neutral-700 hover:bg-neutral-100' => ! request()->routeIs($item['match']),
                       ])
                       @if (request()->routeIs($item['match'])) aria-current="page" @endif>{{ $item['label'] }}@if (! empty($item['badge']))<span class="mr-1 rounded-full bg-brand-accent px-1.5 py-0.5 text-xs text-white">{{ $item['badge'] }}<span class="sr-only"> پیام خوانده‌نشده</span></span>@endif</a>
                @endforeach
                <form method="POST" action="{{ route('account.logout') }}" class="shrink-0">
                    @csrf
                    <button type="submit" class="w-full rounded px-3 py-2 text-right text-red-700 hover:bg-red-50">خروج</button>
                </form>
            </nav>
        </aside>

        <div class="min-w-0">
            @if (session('status'))
                <div class="mb-6 rounded border border-green-200 bg-green-50 p-3 text-sm text-green-700">{{ session('status') }}</div>
            @endif

            @yield('account-content')
        </div>
    </div>
</div>
@endsection
