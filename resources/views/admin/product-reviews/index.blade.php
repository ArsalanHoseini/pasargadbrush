@extends('admin.layout')

@section('title', 'نظرات محصولات')

@php
$statusLabels = \App\Models\ProductReview::STATUS_LABELS;
@endphp

@section('content')
<h1 class="mb-6 text-lg font-semibold">نظرات محصولات</h1>

<div class="mb-6 flex gap-2 text-sm">
    <a href="{{ route('admin.product-reviews.index', ['status' => 'all']) }}"
       class="rounded px-3 py-1.5 {{ $status === 'all' ? 'bg-neutral-900 text-white' : 'border border-neutral-300' }}">همه</a>
    @foreach ($statusLabels as $value => $label)
        <a href="{{ route('admin.product-reviews.index', ['status' => $value]) }}"
           class="rounded px-3 py-1.5 {{ $status === $value ? 'bg-neutral-900 text-white' : 'border border-neutral-300' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

@if ($reviews->isEmpty())
    <p class="text-sm text-neutral-500">نظری ثبت نشده است.</p>
@else
    <div class="space-y-4">
        @foreach ($reviews as $review)
            <div class="rounded-lg border border-neutral-200 p-4 {{ $review->status === 'pending' ? 'bg-amber-50' : 'bg-white' }}">
                <div class="mb-2 flex flex-wrap items-center justify-between gap-2 text-xs text-neutral-500">
                    <span>
                        <strong class="text-neutral-800">{{ $review->author_name }}</strong>
                        @if ($review->customer_id)
                            (مشتری دارای حساب)
                        @endif
                        — روی
                        @if ($review->product)
                            <a href="{{ route('products.show', $review->product) }}" class="underline" target="_blank">{{ $review->product->name }}</a>
                        @else
                            محصول حذف‌شده
                        @endif
                        @if ($review->rating)
                            — <span aria-label="{{ $review->rating }} از ۵">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</span>
                        @endif
                    </span>
                    <span>{{ persian_date($review->created_at, \App\Support\PersianDate::COMPACT) }} — {{ $statusLabels[$review->status] }}</span>
                </div>

                <p class="mb-3 whitespace-pre-line text-sm text-neutral-700">{{ $review->body }}</p>

                <div class="flex gap-3 text-xs">
                    @if ($review->status !== 'approved')
                        <form method="POST" action="{{ route('admin.product-reviews.approve', $review) }}">
                            @csrf
                            <button type="submit" class="text-green-700 hover:underline">تأیید</button>
                        </form>
                    @endif
                    @if ($review->status !== 'spam')
                        <form method="POST" action="{{ route('admin.product-reviews.spam', $review) }}">
                            @csrf
                            <button type="submit" class="text-amber-700 hover:underline">اسپم</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('admin.product-reviews.destroy', $review) }}"
                          onsubmit="return confirm('حذف این نظر قطعی است. ادامه می‌دهید؟');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-700 hover:underline">حذف</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-6">{{ $reviews->links() }}</div>
@endif
@endsection
