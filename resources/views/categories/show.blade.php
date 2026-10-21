@extends('layouts.public')

@section('title', $category->name)
@section('meta_description', $category->meta_description ?? $category->name)
@section('canonical', url()->current())

@section('content')
<div class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
    <nav class="mb-4 text-xs text-neutral-500" aria-label="مسیر صفحه">
        <a href="{{ route('home') }}" class="hover:text-neutral-800">صفحه اصلی</a>
        <span class="mx-1">/</span>
        <span class="text-neutral-700">{{ $category->name }}</span>
    </nav>

    <h1 class="mb-2 text-2xl font-bold text-neutral-900">{{ $category->name }}</h1>
    @if ($category->description)
        <p class="mb-8 max-w-2xl text-sm text-neutral-600">{{ $category->description }}</p>
    @endif

    @if ($products->isEmpty())
        <p class="text-sm text-neutral-500">محصولی در این دسته ثبت نشده است.</p>
    @else
        <p class="mb-6 text-xs text-neutral-500">{{ $products->total() }} محصول</p>

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
