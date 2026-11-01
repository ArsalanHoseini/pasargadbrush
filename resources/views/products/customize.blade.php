@extends('layouts.public')

@section('title', 'ابعاد سفارش — '.$product->name)

@section('content')
<div class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
    <nav class="mb-4 text-xs text-neutral-500">
        <a href="{{ route('products.show', $product) }}" class="hover:text-neutral-900">{{ $product->name }}</a>
        <span aria-hidden="true">/</span>
        <span>ابعاد سفارش</span>
    </nav>

    <h1 class="mb-2 text-2xl font-bold text-neutral-900">ابعاد مورد نیاز را وارد کنید</h1>
    <p class="mb-6 text-sm text-neutral-600">
        این محصول بر اساس ابعاد شما ساخته می‌شود. اندازه‌ی هر بخش را مطابق شکل زیر، دقیق وارد کنید.
    </p>

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-neutral-200 bg-white p-4 text-sm">
        <div>
            <p class="font-medium text-neutral-900">{{ $product->name }}</p>
            <p class="text-xs text-neutral-500">{{ $variant->label }}</p>
        </div>
        <div class="text-left">
            <p class="text-xs text-neutral-500">قیمت پایه</p>
            <p class="font-medium">{{ \App\Support\Money::toman($variant->price_rial) }}</p>
        </div>
        <p class="w-full rounded bg-amber-50 p-2 text-xs text-amber-800">
            قیمت نهایی پس از بررسی ابعاد توسط کارشناس تأیید و به شما اعلام می‌شود.
        </p>
    </div>

    @if ($errors->any())
        <div class="mb-6 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            لطفاً موارد مشخص‌شده را اصلاح کنید.
            @error('quantity')
                <p class="mt-1">{{ $message }}</p>
            @enderror
        </div>
    @endif

    <form method="POST" action="{{ route('cart.items.store') }}" class="space-y-6 rounded-lg border border-neutral-200 bg-white p-6">
        @csrf
        <input type="hidden" name="product_variant_id" value="{{ $variant->id }}">

        <x-dimension-fields :product="$product" />

        <div class="flex flex-wrap items-end justify-between gap-4 border-t border-neutral-100 pt-4">
            <div>
                <label for="quantity" class="mb-1 block text-sm font-medium text-neutral-800">تعداد</label>
                <input id="quantity" name="quantity" type="number" min="1"
                       max="{{ min($variant->stock_quantity ?? \App\Services\Cart\CartManager::MAX_QUANTITY, \App\Services\Cart\CartManager::MAX_QUANTITY) }}"
                       value="{{ old('quantity', $quantity) }}" dir="ltr" required
                       class="w-24 rounded border border-neutral-300 px-3 py-2 text-sm">
            </div>
            <button type="submit" class="rounded-full bg-brand-accent px-8 py-3 text-sm font-semibold text-white hover:bg-brand-accent-dark">
                افزودن به سبد خرید
            </button>
        </div>
    </form>
</div>
@endsection
