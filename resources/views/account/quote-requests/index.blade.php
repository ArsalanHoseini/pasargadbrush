@extends('account.layout')

@section('title', 'استعلام‌های قیمت')

@section('account-content')
<h1 class="mb-6 text-xl font-bold text-neutral-900">استعلام‌های قیمت</h1>

@forelse ($quoteRequests as $quoteRequest)
    <article class="mb-4 rounded-lg border border-neutral-200 bg-white p-5 text-sm">
        <div class="mb-2 flex flex-wrap items-baseline justify-between gap-2">
            <h2 class="font-bold text-neutral-900">
                @if ($quoteRequest->product)
                    <a href="{{ route('products.show', $quoteRequest->product) }}" class="hover:underline">{{ $quoteRequest->product->name }}</a>
                @else
                    درخواست عمومی
                @endif
            </h2>
            <span class="text-xs text-neutral-500">{{ \App\Support\PersianDate::format($quoteRequest->created_at, 'd MMMM y') }}</span>
        </div>

        <p class="mb-2">
            <span class="rounded bg-neutral-100 px-2 py-0.5 text-xs">{{ \App\Http\Controllers\Account\QuoteRequestController::STATUS_LABELS[$quoteRequest->status] ?? $quoteRequest->status }}</span>
            @if ($quoteRequest->order)
                <a href="{{ route('account.orders.show', $quoteRequest->order) }}" class="mr-2 text-xs text-brand-accent-dark hover:underline">
                    سفارش ثبت شد: <span dir="ltr">{{ $quoteRequest->order->order_number }}</span>
                </a>
            @endif
        </p>

        @if ($quoteRequest->requested_quantity)
            <p class="text-xs text-neutral-600">تعداد درخواستی: {{ $quoteRequest->requested_quantity }}</p>
        @endif
        @include('orders.partials.customization', ['customization' => $quoteRequest->customization])
        <p class="mt-2 whitespace-pre-line text-neutral-700">{{ $quoteRequest->message }}</p>
    </article>
@empty
    <div class="rounded-lg border border-neutral-200 bg-white p-5 text-sm text-neutral-500">
        هنوز استعلام قیمتی نفرستاده‌اید. برای محصولات سفارشی، از صفحه‌ی همان محصول درخواست استعلام قیمت بفرستید.
    </div>
@endforelse

<div class="mt-6">{{ $quoteRequests->links() }}</div>
@endsection
