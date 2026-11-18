@extends('admin.layout')

@section('title', 'گزارش فروش')

@php
    $summary = $report['summary'];
    $maxMonthRevenue = max([1, ...array_column($report['months'], 'revenue_rial')]);
    $rangeQuery = $preset ? ['preset' => $preset] : request()->only(['from', 'to']);
    $money = fn (int $rial) => \App\Support\Money::toman($rial);
@endphp

@section('content')
<x-admin.page-header title="گزارش فروش"
    :description="persian_date($from, \App\Support\PersianDate::DATE).' تا '.persian_date($to, \App\Support\PersianDate::DATE).' — بدون سفارش‌های لغوشده'">
    <x-slot:actions>
        <x-admin.button :href="route('admin.reports.export', $rangeQuery)" variant="secondary" icon="download">خروجی Excel فروش محصولات</x-admin.button>
    </x-slot:actions>
</x-admin.page-header>

<div class="mb-4 flex flex-wrap items-center gap-2">
    @foreach (\App\Http\Controllers\Admin\ReportController::PRESETS as $key => $label)
        <a href="{{ route('admin.reports.index', ['preset' => $key]) }}"
           @class(['rounded-lg px-3 py-1.5 text-sm', 'bg-brand-charcoal text-white' => $preset === $key, 'border border-neutral-200 bg-white text-neutral-600 hover:bg-neutral-50' => $preset !== $key])>{{ $label }}</a>
    @endforeach

    <form method="GET" action="{{ route('admin.reports.index') }}" class="flex flex-wrap items-center gap-2 sm:mr-auto">
        <x-admin.filter-date-range />
        <x-admin.button variant="secondary" icon="funnel">نمایش بازه</x-admin.button>
    </form>
</div>

<div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
    @foreach ([
        ['مبلغ فروش', $money($summary['revenue_rial']), 'پرداخت‌شده: '.$money($summary['paid_revenue_rial'])],
        ['تعداد سفارش', number_format($summary['order_count']), $summary['cancelled_count'] ? $summary['cancelled_count'].' سفارش لغوشده (حساب نشده)' : null],
        ['میانگین هر سفارش', $money($summary['average_rial']), null],
        ['تعداد کالای فروخته‌شده', number_format($summary['items_sold']), null],
    ] as [$label, $value, $hint])
        <div class="rounded-xl border border-neutral-200 bg-white p-4">
            <p class="text-xs text-neutral-500">{{ $label }}</p>
            <p class="mt-1 text-lg font-bold" dir="ltr" style="text-align: right">{{ $value }}</p>
            @if ($hint)
                <p class="mt-0.5 text-xs text-neutral-400">{{ $hint }}</p>
            @endif
        </div>
    @endforeach
</div>

@if ($summary['order_count'] === 0)
    <x-admin.empty-state icon="banknotes">در این بازه سفارشی ثبت نشده است.</x-admin.empty-state>
@else
    <div class="mb-6 grid gap-6 xl:grid-cols-3">
        <section class="rounded-xl border border-neutral-200 bg-white p-5 xl:col-span-2">
            <h2 class="mb-4 font-semibold">فروش ماهانه</h2>
            <ul class="space-y-3">
                @foreach ($report['months'] as $month)
                    <li class="grid grid-cols-[6rem_1fr_auto] items-center gap-3 text-sm">
                        <span class="text-neutral-600">{{ $month['label'] }}</span>
                        <span class="h-3 rounded-full bg-neutral-100">
                            <span class="block h-3 rounded-full bg-brand-accent" style="width: {{ max(2, round($month['revenue_rial'] / $maxMonthRevenue * 100)) }}%"></span>
                        </span>
                        <span class="whitespace-nowrap text-xs text-neutral-500"><span dir="ltr">{{ $money($month['revenue_rial']) }}</span> — {{ $month['order_count'] }} سفارش</span>
                    </li>
                @endforeach
            </ul>
        </section>

        <section class="rounded-xl border border-neutral-200 bg-white p-5">
            <h2 class="mb-4 font-semibold">بر اساس وضعیت</h2>
            <ul class="divide-y divide-neutral-100 text-sm">
                @foreach ($report['statuses'] as $row)
                    <li class="flex justify-between gap-2 py-2"><span>{{ $row['label'] }}</span><span class="text-neutral-500">{{ $row['order_count'] }} سفارش</span></li>
                @endforeach
            </ul>
        </section>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <section class="overflow-x-auto rounded-xl border border-neutral-200 bg-white xl:col-span-2">
            <h2 class="px-5 pt-5 font-semibold">پرفروش‌ترین محصولات</h2>
            <table class="mt-3 w-full text-sm">
                <thead class="border-y border-neutral-200 bg-neutral-50 text-right text-xs text-neutral-500">
                    <tr>
                        <th class="px-4 py-2 font-medium">#</th>
                        <th class="px-4 py-2 font-medium">محصول</th>
                        <th class="px-4 py-2 font-medium">تعداد</th>
                        <th class="px-4 py-2 font-medium">مبلغ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @foreach (array_slice($report['products'], 0, 20) as $row)
                        <tr>
                            <td class="px-4 py-2 text-neutral-400">{{ $loop->iteration }}</td>
                            <td class="px-4 py-2">
                                @if ($row['product_id'])
                                    <a href="{{ route('admin.products.edit', $row['product_id']) }}" class="hover:text-brand-accent-dark">{{ $row['name'] }}</a>
                                @else
                                    {{ $row['name'] }}
                                @endif
                            </td>
                            <td class="px-4 py-2">{{ number_format($row['quantity']) }}</td>
                            <td class="whitespace-nowrap px-4 py-2" dir="ltr">{{ $money($row['revenue_rial']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <div class="space-y-6">
            @foreach (['sources' => 'بر اساس منبع', 'provinces' => 'بر اساس استان'] as $key => $title)
                <section class="rounded-xl border border-neutral-200 bg-white p-5">
                    <h2 class="mb-4 font-semibold">{{ $title }}</h2>
                    <ul class="divide-y divide-neutral-100 text-sm">
                        @foreach (array_slice($report[$key], 0, 10) as $row)
                            <li class="flex justify-between gap-2 py-2">
                                <span>{{ $row['label'] }} <span class="text-xs text-neutral-400">({{ $row['order_count'] }})</span></span>
                                <span class="whitespace-nowrap text-neutral-600" dir="ltr">{{ $money($row['revenue_rial']) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </div>
    </div>
@endif
@endsection
