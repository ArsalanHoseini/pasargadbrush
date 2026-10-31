@extends('layouts.public')

@section('title', $product->meta_title ?? $product->name)
@section('meta_description', $product->meta_description ?? \Illuminate\Support\Str::limit(strip_tags($product->description ?? ''), 150))
@section('canonical', url()->current())

@push('structured-data')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Product",
    "name": {!! json_encode($product->name, JSON_UNESCAPED_UNICODE) !!},
    "sku": {!! json_encode($product->sku) !!},
    @if ($product->images->isNotEmpty())
    "image": {!! json_encode($product->images->first()->url()) !!},
    @endif
    "description": {!! json_encode(strip_tags($product->description ?? ''), JSON_UNESCAPED_UNICODE) !!}
    @if ($product->type === 'priced' && $product->variants->isNotEmpty())
    ,"offers": {
        "@@type": "AggregateOffer",
        "priceCurrency": "IRR",
        "lowPrice": {{ $product->variants->min('price_rial') }},
        "highPrice": {{ $product->variants->max('price_rial') }}
    }
    @endif
}
</script>
@endpush

@section('content')
<div class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
    <nav class="mb-6 text-xs text-neutral-500" aria-label="مسیر صفحه">
        <a href="{{ route('home') }}" class="hover:text-neutral-800">صفحه اصلی</a>
        @if ($product->primaryCategory)
            <span class="mx-1">/</span>
            <a href="{{ route('categories.show', $product->primaryCategory) }}" class="hover:text-neutral-800">{{ $product->primaryCategory->name }}</a>
        @endif
        <span class="mx-1">/</span>
        <span class="text-neutral-700">{{ $product->name }}</span>
    </nav>

    <div class="grid gap-10 lg:grid-cols-2">
        <div>
            @if ($product->images->isNotEmpty())
                <div class="mb-3 aspect-square overflow-hidden rounded-lg bg-neutral-100">
                    <img src="{{ $product->images->first()->url() }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                </div>
                @if ($product->images->count() > 1)
                    <div class="grid grid-cols-5 gap-2">
                        @foreach ($product->images as $image)
                            <div class="aspect-square overflow-hidden rounded bg-neutral-100">
                                <img src="{{ $image->url() }}" alt="{{ $product->name }}" class="h-full w-full object-cover" loading="lazy">
                            </div>
                        @endforeach
                    </div>
                @endif
            @else
                <div class="flex aspect-square items-center justify-center rounded-lg bg-neutral-100 text-sm text-neutral-400">
                    بدون تصویر
                </div>
            @endif
        </div>

        <div>
            <h1 class="mb-2 text-2xl font-bold text-neutral-900">{{ $product->name }}</h1>
            <p class="mb-4 text-xs text-neutral-500" dir="ltr">SKU: {{ $product->sku }}</p>

            @if ($product->type === 'priced' && $product->variants->isNotEmpty())
                @if (session('status'))
                    <div class="mb-4 rounded border border-green-200 bg-green-50 p-3 text-sm text-green-700">
                        {{ session('status') }}
                        <a href="{{ route('cart.show') }}" class="font-medium underline">مشاهده سبد خرید</a>
                    </div>
                @endif

                @error('quantity')
                    <div class="mb-4 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ $message }}</div>
                @enderror

                <div class="mb-6 space-y-2">
                    @foreach ($product->variants as $variant)
                        <form method="POST" action="{{ route('cart.items.store') }}"
                              class="flex flex-wrap items-center justify-between gap-3 rounded border border-neutral-200 px-4 py-3">
                            @csrf
                            <input type="hidden" name="product_variant_id" value="{{ $variant->id }}">
                            <span class="text-sm">{{ $variant->label }}</span>
                            <div class="flex items-center gap-3">
                                <span class="font-medium" dir="ltr">{{ \App\Support\Money::toman($variant->price_rial) }}</span>
                                @if ($variant->unavailableReason() === null)
                                    <input type="number" name="quantity" value="1" min="1"
                                           max="{{ min($variant->stock_quantity ?? \App\Services\Cart\CartManager::MAX_QUANTITY, \App\Services\Cart\CartManager::MAX_QUANTITY) }}"
                                           class="w-16 rounded border border-neutral-300 px-2 py-1 text-sm" dir="ltr">
                                    <button type="submit" class="rounded-full bg-brand-accent px-4 py-1.5 text-xs font-semibold text-white hover:bg-brand-accent-dark">
                                        افزودن به سبد
                                    </button>
                                @else
                                    <span class="rounded-full bg-neutral-100 px-4 py-1.5 text-xs font-semibold text-neutral-500">ناموجود</span>
                                @endif
                            </div>
                        </form>
                    @endforeach
                </div>
            @else
                <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    این محصول بر اساس نیاز شما (ابعاد، تیراژ، مشخصات فنی) تولید می‌شود. برای استعلام قیمت با ما تماس بگیرید.
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="#quote-form"
                       class="rounded-full bg-brand-accent px-6 py-3 text-sm font-semibold text-white hover:bg-brand-accent-dark">
                        درخواست استعلام قیمت
                    </a>
                    <a href="tel:{{ setting('contact_mobile') }}"
                       class="rounded-full border border-neutral-300 px-6 py-3 text-sm font-semibold text-neutral-800 hover:border-neutral-500"
                       dir="ltr">
                        {{ setting('contact_mobile') }}
                    </a>
                </div>
            @endif

            @if ($product->description)
                <div class="article-content mt-8 border-t border-neutral-100 pt-6 text-sm leading-7 text-neutral-700">
                    {!! $product->description !!}
                </div>
            @endif

            @if (! empty($product->specifications))
                <div class="mt-8 border-t border-neutral-100 pt-6">
                    <h2 class="mb-3 text-sm font-bold text-neutral-900">مشخصات فنی</h2>
                    <dl class="divide-y divide-neutral-100 text-sm">
                        @foreach ($product->specifications as $key => $value)
                            <div class="flex justify-between gap-4 py-2">
                                <dt class="text-neutral-500">{{ $key }}</dt>
                                <dd class="font-medium text-neutral-900">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endif
        </div>
    </div>

    @if ($product->type === 'quote')
        <div class="mt-12 max-w-xl">
            <x-quote-request-form :product="$product" />
        </div>
    @endif

    @if ($related->isNotEmpty())
        <div class="mt-16">
            <h2 class="mb-6 text-lg font-bold text-neutral-900">محصولات مرتبط</h2>
            <div class="grid grid-cols-2 gap-5 sm:grid-cols-4">
                @foreach ($related as $item)
                    <a href="{{ route('products.show', $item) }}" class="group rounded-lg border border-neutral-200 bg-white p-3 transition hover:shadow-md">
                        <div class="mb-3 aspect-square overflow-hidden rounded bg-neutral-100">
                            @if ($item->images->isNotEmpty())
                                <img src="{{ $item->images->first()->url() }}" alt="{{ $item->name }}" class="h-full w-full object-cover" loading="lazy">
                            @endif
                        </div>
                        <h3 class="text-sm font-medium text-neutral-900 group-hover:text-brand-accent-dark">{{ $item->name }}</h3>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
