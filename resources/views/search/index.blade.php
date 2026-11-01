@extends('layouts.public')

@section('title', $query !== '' ? 'جستجو: '.$query : 'جستجو')
@section('robots', 'noindex, follow')
@section('meta_description', 'جستجوی محصولات پاسارگاد برس')

@section('content')
<div class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
    <form method="GET" action="{{ route('search.index') }}" class="mb-8 flex max-w-md gap-2">
        <input type="search" name="q" value="{{ $query }}" placeholder="جستجوی محصول…" autofocus
               class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
        <button type="submit" class="shrink-0 rounded bg-brand-charcoal px-4 py-2 text-sm text-white hover:bg-neutral-800">
            جستجو
        </button>
    </form>

    @if ($query === '')
        <p class="text-sm text-neutral-500">عبارت مورد نظر برای جستجو را وارد کنید.</p>
    @elseif ($products->isEmpty())
        <p class="text-sm text-neutral-500">نتیجه‌ای برای «{{ $query }}» پیدا نشد.</p>
    @else
        <p class="mb-6 text-xs text-neutral-500">{{ $products->total() }} نتیجه برای «{{ $query }}»</p>

        <div class="grid grid-cols-2 gap-5 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($products as $product)
                <a href="{{ route('products.show', $product) }}" class="group rounded-lg border border-neutral-200 bg-white p-3 transition hover:shadow-md">
                    <div class="mb-3 aspect-square overflow-hidden rounded bg-neutral-100">
                        @if ($product->images->isNotEmpty())
                            <img src="{{ $product->images->first()->url() }}" alt="{{ $product->name }}"
                                 class="h-full w-full object-cover transition group-hover:scale-105" loading="lazy">
                        @else
                            <div class="flex h-full w-full items-center justify-center text-xs text-neutral-400">بدون تصویر</div>
                        @endif
                    </div>
                    <h2 class="text-sm font-medium text-neutral-900 group-hover:text-brand-accent-dark">{{ $product->name }}</h2>
                </a>
            @endforeach
        </div>

        <div class="mt-8">{{ $products->links() }}</div>
    @endif
</div>
@endsection
