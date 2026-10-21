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
                <div class="mb-6 space-y-2">
                    @foreach ($product->variants as $variant)
                        <div class="flex items-center justify-between rounded border border-neutral-200 px-4 py-3">
                            <span class="text-sm">{{ $variant->label }}</span>
                            <span class="font-medium" dir="ltr">{{ \App\Support\Money::toman($variant->price_rial) }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    این محصول بر اساس نیاز شما (ابعاد، تیراژ، مشخصات فنی) تولید می‌شود. برای استعلام قیمت با ما تماس بگیرید.
                </div>
            @endif

            <div class="flex flex-wrap gap-3">
                <a href="tel:{{ setting('contact_mobile') }}"
                   class="rounded-full bg-brand-accent px-6 py-3 text-sm font-semibold text-white hover:bg-brand-accent-dark">
                    درخواست قیمت و تماس
                </a>
                <a href="mailto:{{ setting('contact_email') }}?subject={{ rawurlencode('استعلام قیمت: '.$product->name) }}"
                   class="rounded-full border border-neutral-300 px-6 py-3 text-sm font-semibold text-neutral-800 hover:border-neutral-500">
                    ارسال ایمیل استعلام
                </a>
            </div>

            @if ($product->description)
                <div class="article-content mt-8 border-t border-neutral-100 pt-6 text-sm leading-7 text-neutral-700">
                    {!! $product->description !!}
                </div>
            @endif
        </div>
    </div>

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
