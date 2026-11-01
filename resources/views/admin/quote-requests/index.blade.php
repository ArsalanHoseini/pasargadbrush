@extends('admin.layout')

@section('title', 'درخواست‌های استعلام قیمت')

@php
$statusLabels = ['new' => 'جدید', 'contacted' => 'تماس گرفته شد', 'quoted' => 'قیمت ارسال شد', 'closed' => 'بسته شده'];
@endphp

@section('content')
<h1 class="mb-6 text-lg font-semibold">درخواست‌های استعلام قیمت</h1>

<div class="mb-6 flex gap-2 text-sm">
    <a href="{{ route('admin.quote-requests.index') }}"
       class="rounded px-3 py-1.5 {{ ! $status ? 'bg-neutral-900 text-white' : 'border border-neutral-300' }}">همه</a>
    @foreach ($statusLabels as $value => $label)
        <a href="{{ route('admin.quote-requests.index', ['status' => $value]) }}"
           class="rounded px-3 py-1.5 {{ $status === $value ? 'bg-neutral-900 text-white' : 'border border-neutral-300' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

@if ($quoteRequests->isEmpty())
    <p class="text-sm text-neutral-500">درخواستی ثبت نشده است.</p>
@else
    <div class="overflow-x-auto rounded-lg border border-neutral-200 bg-white">
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
