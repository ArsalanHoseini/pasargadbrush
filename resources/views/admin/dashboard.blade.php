@extends('admin.layout')

@section('title', 'داشبورد')

@php
    $statusLabels = \App\Models\Order::STATUS_LABELS;
    $statusTones = [
        'pending' => 'bg-amber-50 text-amber-700',
        'confirmed' => 'bg-blue-50 text-blue-700',
        'processing' => 'bg-indigo-50 text-indigo-700',
        'shipped' => 'bg-cyan-50 text-cyan-700',
        'completed' => 'bg-green-50 text-green-700',
        'cancelled' => 'bg-neutral-100 text-neutral-500',
    ];
    $toneClasses = [
        'red' => 'bg-red-50 text-red-600',
        'amber' => 'bg-amber-50 text-amber-600',
        'neutral' => 'bg-neutral-100 text-neutral-600',
    ];
    $maxDailyRevenue = max(1, max(array_column($dailySales, 'revenue_rial')));
    $hasSalesInChart = collect($dailySales)->sum('order_count') > 0;
@endphp

@section('content')
<x-admin.page-header title="داشبورد" :description="'سلام '.auth()->user()->name.'، امروز '.persian_date(now(), \App\Support\PersianDate::DATE)">
    <x-slot:actions>
        <x-admin.button :href="route('admin.orders.create')" icon="plus">ثبت سفارش دستی</x-admin.button>
        <x-admin.button :href="route('admin.products.create')" variant="secondary" icon="cube">محصول جدید</x-admin.button>
    </x-slot:actions>
</x-admin.page-header>

{{-- Sales KPIs --}}
<div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ($salesPeriods as $period)
        <div class="rounded-xl border border-neutral-200 bg-white p-5">
            <div class="mb-3 flex items-center justify-between">
                <p class="text-sm text-neutral-500">فروش {{ $period['label'] }}</p>
                @if ($period['change_percent'] !== null)
                    <span @class([
                        'rounded-full px-2 py-0.5 text-xs font-medium',
                        'bg-green-50 text-green-700' => $period['change_percent'] >= 0,
                        'bg-red-50 text-red-700' => $period['change_percent'] < 0,
                    ]) dir="ltr" title="نسبت به دوره قبل">
                        {{ $period['change_percent'] >= 0 ? '▲' : '▼' }} {{ abs($period['change_percent']) }}%
                    </span>
                @endif
            </div>
            <p class="text-2xl font-bold" dir="ltr">{{ \App\Support\Money::toman($period['revenue_rial']) }}</p>
            <p class="mt-1 text-xs text-neutral-500">{{ $period['order_count'] }} سفارش</p>
        </div>
    @endforeach

    <div class="rounded-xl border border-neutral-200 bg-white p-5">
        <p class="mb-3 text-sm text-neutral-500">مشتریان</p>
        <p class="text-2xl font-bold">{{ number_format($customerCounts['customers']) }}</p>
        <p class="mt-1 text-xs text-neutral-500">{{ $customerCounts['new_customers_30_days'] }} مشتری جدید در ۳۰ روز اخیر</p>
    </div>
</div>

<div class="mb-6 grid gap-6 xl:grid-cols-3">
    {{-- 30-day chart --}}
    <section class="rounded-xl border border-neutral-200 bg-white p-5 xl:col-span-2" aria-labelledby="sales-chart-title">
        <div class="mb-4 flex items-center justify-between">
            <h2 id="sales-chart-title" class="font-semibold">فروش ۳۰ روز اخیر</h2>
            <span class="text-xs text-neutral-500">بدون سفارش‌های لغوشده</span>
        </div>

        @if ($hasSalesInChart)
            <div class="flex h-56 items-end gap-1" x-data="{ active: null }" @mouseleave="active = null">
                @foreach ($dailySales as $index => $day)
                    <div class="group relative flex h-full flex-1 items-end" @mouseenter="active = {{ $index }}">
                        <div @class([
                                'w-full rounded-t transition',
                                'bg-brand-accent group-hover:bg-brand-accent-dark' => $day['revenue_rial'] > 0,
                                'bg-neutral-100' => $day['revenue_rial'] === 0,
                             ])
                             style="height: {{ $day['revenue_rial'] > 0 ? max(4, round($day['revenue_rial'] / $maxDailyRevenue * 100)) : 2 }}%"></div>
                        <div x-cloak x-show="active === {{ $index }}"
                             class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-2 w-max -translate-x-1/2 rounded-lg bg-neutral-900 px-3 py-2 text-xs text-white shadow-lg">
                            <p class="mb-0.5 text-neutral-300">{{ persian_date($day['date'], \App\Support\PersianDate::DATE) }}</p>
                            <p dir="ltr" class="text-right">{{ \App\Support\Money::toman($day['revenue_rial']) }}</p>
                            <p>{{ $day['order_count'] }} سفارش</p>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-2 flex justify-between text-xs text-neutral-400">
                <span>{{ persian_date($dailySales[0]['date'], 'd MMMM') }}</span>
                <span>{{ persian_date($dailySales[intdiv(count($dailySales), 2)]['date'], 'd MMMM') }}</span>
                <span>امروز</span>
            </div>
        @else
            <div class="flex h-56 flex-col items-center justify-center gap-2 rounded-lg bg-neutral-50 text-sm text-neutral-500">
                <x-admin.icon name="banknotes" class="size-8 text-neutral-300" />
                در ۳۰ روز اخیر سفارشی ثبت نشده است.
            </div>
        @endif
    </section>

    {{-- Needs attention --}}
    <section class="rounded-xl border border-neutral-200 bg-white p-5" aria-labelledby="action-items-title">
        <h2 id="action-items-title" class="mb-4 font-semibold">نیاز به اقدام</h2>

        @if (empty($actionItems))
            <div class="flex flex-col items-center gap-2 py-10 text-center text-sm text-neutral-500">
                <x-admin.icon name="check" class="size-8 text-green-500" />
                کار معوقی وجود ندارد.
            </div>
        @else
            <ul class="space-y-2">
                @foreach ($actionItems as $item)
                    <li>
                        <a href="{{ $item['url'] }}" class="flex items-center gap-3 rounded-lg border border-neutral-100 p-3 transition hover:border-neutral-300 hover:bg-neutral-50">
                            <span class="flex size-9 items-center justify-center rounded-lg {{ $toneClasses[$item['tone']] }}">
                                <x-admin.icon :name="$item['icon']" />
                            </span>
                            <span class="flex-1 text-sm">{{ $item['label'] }}</span>
                            <span class="text-lg font-bold">{{ $item['count'] }}</span>
                            <x-admin.icon name="chevron-left" class="size-4 text-neutral-400" />
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</div>

<div class="mb-6 grid gap-6 xl:grid-cols-3">
    {{-- Recent orders --}}
    <section class="rounded-xl border border-neutral-200 bg-white xl:col-span-2">
        <div class="flex items-center justify-between border-b border-neutral-100 px-5 py-4">
            <h2 class="font-semibold">آخرین سفارش‌ها</h2>
            <a href="{{ route('admin.orders.index') }}" class="text-sm text-brand-accent-dark hover:underline">مشاهده همه</a>
        </div>

        @if ($recentOrders->isEmpty())
            <p class="p-5 text-sm text-neutral-500">هنوز سفارشی ثبت نشده است.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-neutral-100">
                        @foreach ($recentOrders as $order)
                            <tr class="hover:bg-neutral-50">
                                <td class="px-5 py-3">
                                    <a href="{{ route('admin.orders.edit', $order) }}" class="font-medium hover:text-brand-accent-dark" dir="ltr">{{ $order->order_number }}</a>
                                </td>
                                <td class="px-5 py-3">{{ ($order->customer_name ?? $order->customer?->name) ?? '—' }}</td>
                                <td class="px-5 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-xs {{ $statusTones[$order->status] ?? '' }}">{{ $statusLabels[$order->status] ?? $order->status }}</span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3" dir="ltr">{{ \App\Support\Money::toman($order->total_rial) }}</td>
                                <td class="whitespace-nowrap px-5 py-3 text-xs text-neutral-500">{{ persian_date($order->placed_at, \App\Support\PersianDate::COMPACT) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- Top products --}}
    <section class="rounded-xl border border-neutral-200 bg-white">
        <div class="border-b border-neutral-100 px-5 py-4">
            <h2 class="font-semibold">پرفروش‌ترین‌ها <span class="text-xs font-normal text-neutral-500">(۳۰ روز اخیر)</span></h2>
        </div>

        @if ($topProducts->isEmpty())
            <p class="p-5 text-sm text-neutral-500">هنوز فروشی ثبت نشده است.</p>
        @else
            <ol class="divide-y divide-neutral-100 text-sm">
                @foreach ($topProducts as $rank => $topProduct)
                    <li class="flex items-center gap-3 px-5 py-3">
                        <span class="flex size-6 items-center justify-center rounded-full bg-neutral-100 text-xs font-medium">{{ $rank + 1 }}</span>
                        <div class="min-w-0 flex-1">
                            @if ($topProduct->product_id)
                                <a href="{{ route('admin.products.edit', $topProduct->product_id) }}" class="block truncate hover:text-brand-accent-dark">{{ $topProduct->name }}</a>
                            @else
                                <span class="block truncate">{{ $topProduct->name }}</span>
                            @endif
                            <span class="text-xs text-neutral-500">{{ (int) $topProduct->quantity }} عدد</span>
                        </div>
                        <span class="whitespace-nowrap text-xs" dir="ltr">{{ \App\Support\Money::toman((int) $topProduct->revenue_rial) }}</span>
                    </li>
                @endforeach
            </ol>
        @endif
    </section>
</div>

<div class="grid gap-6 xl:grid-cols-3">
    {{-- Recent quote requests --}}
    <section class="rounded-xl border border-neutral-200 bg-white xl:col-span-2">
        <div class="flex items-center justify-between border-b border-neutral-100 px-5 py-4">
            <h2 class="font-semibold">آخرین درخواست‌های استعلام قیمت</h2>
            <a href="{{ route('admin.quote-requests.index') }}" class="text-sm text-brand-accent-dark hover:underline">مشاهده همه</a>
        </div>

        @if ($recentQuoteRequests->isEmpty())
            <p class="p-5 text-sm text-neutral-500">هنوز درخواستی ثبت نشده است.</p>
        @else
            <ul class="divide-y divide-neutral-100 text-sm">
                @foreach ($recentQuoteRequests as $quoteRequest)
                    <li>
                        <a href="{{ route('admin.quote-requests.edit', $quoteRequest) }}" class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-neutral-50">
                            <span class="min-w-0 truncate">
                                @if ($quoteRequest->status === 'new')
                                    <span class="ml-1 inline-block size-2 rounded-full bg-brand-accent" title="جدید"></span>
                                @endif
                                {{ $quoteRequest->name }} — <span class="text-neutral-500">{{ $quoteRequest->product?->name ?? 'عمومی' }}</span>
                            </span>
                            <span class="shrink-0 text-xs text-neutral-400">{{ persian_date($quoteRequest->created_at, \App\Support\PersianDate::COMPACT) }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- Catalog at a glance --}}
    <section class="rounded-xl border border-neutral-200 bg-white p-5">
        <h2 class="mb-4 font-semibold">کاتالوگ</h2>
        <div class="grid grid-cols-2 gap-3 text-sm">
            <a href="{{ route('admin.products.index', ['status' => 'active']) }}" class="rounded-lg bg-neutral-50 p-3 hover:bg-neutral-100">
                <p class="text-xs text-neutral-500">محصولات فعال</p>
                <p class="mt-1 text-lg font-bold">{{ $activeProductCount }} <span class="text-xs font-normal text-neutral-400">/ {{ $productCount }}</span></p>
            </a>
            <a href="{{ route('admin.categories.index') }}" class="rounded-lg bg-neutral-50 p-3 hover:bg-neutral-100">
                <p class="text-xs text-neutral-500">دسته‌بندی‌ها</p>
                <p class="mt-1 text-lg font-bold">{{ $categoryCount }}</p>
            </a>
            <a href="{{ route('admin.quote-requests.index', ['status' => 'new']) }}" class="rounded-lg bg-neutral-50 p-3 hover:bg-neutral-100">
                <p class="text-xs text-neutral-500">استعلام جدید</p>
                <p @class(['mt-1 text-lg font-bold', 'text-brand-accent-dark' => $newQuoteRequestCount > 0])>{{ $newQuoteRequestCount }}</p>
            </a>
            <a href="{{ route('admin.media.index') }}" class="rounded-lg bg-neutral-50 p-3 hover:bg-neutral-100">
                <p class="text-xs text-neutral-500">کتابخانه مدیا</p>
                <p class="mt-1 text-sm font-medium text-brand-accent-dark">آپلود فایل ←</p>
            </a>
        </div>
    </section>
</div>
@endsection
