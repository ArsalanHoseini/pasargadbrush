@extends('admin.layout')

@section('title', 'داشبورد')

@section('content')
<h1 class="mb-6 text-lg font-semibold">داشبورد مدیریت</h1>

<div class="mb-8 grid gap-4 sm:grid-cols-3">
    <div class="rounded-lg border border-neutral-200 bg-white p-5">
        <p class="text-xs text-neutral-500">محصولات فعال</p>
        <p class="text-2xl font-bold">{{ $activeProductCount }} <span class="text-sm font-normal text-neutral-400">/ {{ $productCount }}</span></p>
    </div>
    <div class="rounded-lg border border-neutral-200 bg-white p-5">
        <p class="text-xs text-neutral-500">دسته‌بندی‌ها</p>
        <p class="text-2xl font-bold">{{ $categoryCount }}</p>
    </div>
    <div class="rounded-lg border border-neutral-200 bg-white p-5">
        <p class="text-xs text-neutral-500">درخواست استعلام جدید</p>
        <p class="text-2xl font-bold {{ $newQuoteRequestCount > 0 ? 'text-brand-accent-dark' : '' }}">{{ $newQuoteRequestCount }}</p>
    </div>
</div>

<div class="rounded-lg border border-neutral-200 bg-white p-5">
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-sm font-medium">آخرین درخواست‌های استعلام قیمت</h2>
        <a href="{{ route('admin.quote-requests.index') }}" class="text-sm text-brand-accent-dark hover:underline">مشاهده همه</a>
    </div>

    @if ($recentQuoteRequests->isEmpty())
        <p class="text-sm text-neutral-500">هنوز درخواستی ثبت نشده است.</p>
    @else
        <ul class="divide-y divide-neutral-100 text-sm">
            @foreach ($recentQuoteRequests as $quoteRequest)
                <li class="flex items-center justify-between py-2">
                    <a href="{{ route('admin.quote-requests.edit', $quoteRequest) }}" class="hover:text-brand-accent-dark">
                        {{ $quoteRequest->name }} — {{ $quoteRequest->product?->name ?? 'عمومی' }}
                    </a>
                    <span class="text-xs text-neutral-400">{{ $quoteRequest->created_at->format('Y-m-d H:i') }}</span>
                </li>
            @endforeach
        </ul>
    @endif
</div>

<p class="mt-6 text-sm text-neutral-500">
    برای آپلود دستی فایل به <a href="{{ route('admin.media.index') }}" class="text-neutral-900 underline">کتابخانه مدیا</a> بروید.
</p>
@endsection
