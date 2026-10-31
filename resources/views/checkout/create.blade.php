@extends('layouts.public')

@section('title', 'تکمیل سفارش')

@section('content')
<div class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
    <h1 class="mb-6 text-2xl font-bold text-neutral-900">تکمیل سفارش</h1>

    @if ($errors->any())
        <div class="mb-6 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="mb-8 rounded-lg border border-neutral-200 bg-white p-4">
        <h2 class="mb-3 text-sm font-bold text-neutral-900">خلاصه سفارش</h2>
        <ul class="divide-y divide-neutral-100 text-sm">
            @foreach ($cart->items as $item)
                <li class="flex justify-between py-2">
                    <span>{{ $item->variant->product->name }} — {{ $item->variant->label }} × {{ $item->quantity }}</span>
                    <span dir="ltr">{{ \App\Support\Money::toman($item->variant->price_rial * $item->quantity) }}</span>
                </li>
            @endforeach
        </ul>
        <div class="mt-3 flex justify-between border-t border-neutral-100 pt-3 font-bold">
            <span>جمع کل</span>
            <span dir="ltr">{{ \App\Support\Money::toman($total) }}</span>
        </div>
    </div>

    <form method="POST" action="{{ route('checkout.store') }}" class="space-y-4 rounded-lg border border-neutral-200 bg-white p-6">
        @csrf

        <div class="grid gap-3 sm:grid-cols-2">
            <input name="name" placeholder="نام و نام خانوادگی *" value="{{ old('name') }}" required
                   class="rounded border border-neutral-300 px-3 py-2 text-sm">
            <input name="phone" type="tel" inputmode="tel" placeholder="شماره موبایل * (مثال: ۰۹۱۲۱۲۳۴۵۶۷)" value="{{ old('phone') }}" required dir="ltr"
                   class="rounded border border-neutral-300 px-3 py-2 text-sm">
        </div>
        <input name="email" type="email" placeholder="ایمیل (اختیاری)" value="{{ old('email') }}" dir="ltr"
               class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">

        <div class="grid gap-3 sm:grid-cols-2">
            <input name="province" placeholder="استان *" value="{{ old('province') }}" required
                   class="rounded border border-neutral-300 px-3 py-2 text-sm">
            <input name="city" placeholder="شهر *" value="{{ old('city') }}" required
                   class="rounded border border-neutral-300 px-3 py-2 text-sm">
        </div>
        <textarea name="address_line" placeholder="آدرس کامل *" rows="3" required
                  class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">{{ old('address_line') }}</textarea>
        <input name="postal_code" placeholder="کد پستی (اختیاری)" value="{{ old('postal_code') }}" dir="ltr"
               class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">

        <p class="text-xs text-neutral-500">
            پرداخت در این سفارش به‌صورت آنلاین نیست — همکاران ما پس از ثبت سفارش برای هماهنگی پرداخت و ارسال با شما تماس می‌گیرند.
        </p>

        <button type="submit" class="w-full rounded-full bg-brand-accent px-6 py-3 text-sm font-semibold text-white hover:bg-brand-accent-dark">
            ثبت سفارش
        </button>
    </form>
</div>
@endsection
