@extends('admin.layout')

@section('title', 'درخواست‌های استعلام قیمت')

@php
$statusLabels = ['new' => 'جدید', 'contacted' => 'تماس گرفته شد', 'quoted' => 'قیمت ارسال شد', 'closed' => 'بسته شده'];
@endphp

@section('content')
<x-admin.page-header title="درخواست‌های استعلام قیمت" description="درخواست‌های قیمت از فرم محصولات و صفحه تماس" />

<div class="mb-4 flex flex-wrap gap-1 border-b border-neutral-200 text-sm">
    <a href="{{ route('admin.quote-requests.index') }}"
       @class(['-mb-px border-b-2 px-3 py-2', 'border-brand-accent font-medium text-neutral-900' => ! $status, 'border-transparent text-neutral-500 hover:text-neutral-900' => $status])>
        همه <span class="text-xs text-neutral-400">{{ $statusCounts->sum() }}</span>
    </a>
    @foreach ($statusLabels as $value => $label)
        <a href="{{ route('admin.quote-requests.index', ['status' => $value]) }}"
           @class(['-mb-px border-b-2 px-3 py-2', 'border-brand-accent font-medium text-neutral-900' => $status === $value, 'border-transparent text-neutral-500 hover:text-neutral-900' => $status !== $value])>
            {{ $label }} <span class="text-xs text-neutral-400">{{ $statusCounts[$value] ?? 0 }}</span>
        </a>
    @endforeach
</div>

<x-admin.filter-bar :action="route('admin.quote-requests.index')" :keep="['status']" placeholder="نام، موبایل، شرکت یا محصول…" />

@if ($quoteRequests->isEmpty())
    <x-admin.empty-state icon="inbox">{{ request()->hasAny(['q']) ? 'موردی با این فیلترها پیدا نشد.' : 'درخواستی ثبت نشده است.' }}</x-admin.empty-state>
@else
    <div class="overflow-x-auto rounded-xl border border-neutral-200 bg-white">
        <table class="w-full text-sm">
            <thead class="border-b border-neutral-200 bg-neutral-50 text-right">
                <tr>
                    <th class="px-4 py-3 font-medium">نام</th>
                    <th class="px-4 py-3 font-medium">محصول</th>
                    <th class="px-4 py-3 font-medium">تلفن</th>
                    <th class="px-4 py-3 font-medium">وضعیت</th>
                    <th class="px-4 py-3 font-medium">تاریخ</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @foreach ($quoteRequests as $quoteRequest)
                    <tr class="{{ $quoteRequest->status === 'new' ? 'bg-amber-50' : '' }}">
                        <td class="px-4 py-3">{{ $quoteRequest->name }}</td>
                        <td class="px-4 py-3 text-neutral-500">{{ $quoteRequest->product?->name ?? 'عمومی' }}</td>
                        <td class="px-4 py-3 text-neutral-500" dir="ltr">{{ $quoteRequest->phone }}</td>
                        <td class="px-4 py-3">{{ $statusLabels[$quoteRequest->status] }}</td>
                        <td class="px-4 py-3 text-neutral-500">{{ persian_date($quoteRequest->created_at, \App\Support\PersianDate::COMPACT) }}</td>
                        <td class="px-4 py-3 text-left">
                            <a href="{{ route('admin.quote-requests.edit', $quoteRequest) }}" class="text-neutral-600 hover:text-neutral-900">مشاهده</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $quoteRequests->links() }}</div>
@endif
@endsection
