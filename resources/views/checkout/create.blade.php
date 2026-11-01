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
                    <div>
                        <span>{{ $item->title() }} × {{ $item->quantity }}</span>
                        @include('orders.partials.customization', ['customization' => $item->customization])
                    </div>
                    <span class="shrink-0">
                        @if ($item->lineTotalRial() !== null)
                            <span dir="ltr">{{ \App\Support\Money::toman($item->lineTotalRial()) }}</span>
                        @else
                            <span class="text-xs text-neutral-500">پس از بررسی کارشناس</span>
                        @endif
                    </span>
                </li>
            @endforeach
        </ul>
        <div class="mt-3 border-t border-neutral-100 pt-3">
            @include('orders.partials.totals', ['subtotalRial' => $total, 'shipping' => $shipping, 'priceNeedsConfirmation' => $priceNeedsConfirmation])
        </div>
    </div>

    <form method="POST" action="{{ route('checkout.store') }}" class="space-y-4 rounded-lg border border-neutral-200 bg-white p-6">
        @csrf

        @if ($customer)
            <p class="rounded bg-neutral-50 p-3 text-xs text-neutral-600">
                با حساب <span dir="ltr" class="font-medium">{{ $customer->phone }}</span> سفارش می‌دهید — این سفارش در «سفارش‌های من» نمایش داده می‌شود.
            </p>
        @else
            <p class="rounded bg-neutral-50 p-3 text-xs text-neutral-600">
                <a href="{{ route('account.login') }}" class="font-medium text-brand-accent-dark hover:underline">وارد شوید</a>
                تا اطلاعات و آدرس‌هایتان خودکار پر شود و سفارش‌هایتان را در حساب کاربری ببینید (اختیاری).
            </p>
        @endif

        <div class="grid gap-3 sm:grid-cols-2">
            <input name="name" placeholder="نام و نام خانوادگی *" value="{{ old('name', $customer?->name) }}" required aria-label="نام و نام خانوادگی"
                   class="rounded border border-neutral-300 px-3 py-2 text-sm">
            @unless ($customer)
                <input name="phone" type="tel" inputmode="tel" placeholder="شماره موبایل * (مثال: ۰۹۱۲۱۲۳۴۵۶۷)" value="{{ old('phone') }}" required dir="ltr" aria-label="شماره موبایل"
                       class="rounded border border-neutral-300 px-3 py-2 text-sm">
            @endunless
        </div>
        <input name="email" type="email" placeholder="ایمیل (اختیاری)" value="{{ old('email', $customer?->email) }}" dir="ltr" aria-label="ایمیل"
               class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">

        <div class="space-y-3" x-data="{ addressId: @js((string) old('saved_address_id', $savedAddresses->first()?->id ?? '')) }">
            @if ($savedAddresses->isNotEmpty())
                <fieldset class="space-y-2">
                    <legend class="mb-1 text-sm font-medium">آدرس ارسال</legend>
                    @foreach ($savedAddresses as $savedAddress)
                        <label class="flex cursor-pointer gap-3 rounded border p-3 text-sm" :class="addressId === '{{ $savedAddress->id }}' ? 'border-neutral-900' : 'border-neutral-200'">
                            <input type="radio" name="saved_address_id" value="{{ $savedAddress->id }}" x-model="addressId" class="mt-1">
                            <span>
                                <span class="block font-medium">{{ $savedAddress->label ?: $savedAddress->city }}</span>
                                <span class="block text-xs text-neutral-600">{{ $savedAddress->province }}، {{ $savedAddress->city }}، {{ $savedAddress->address_line }}</span>
                            </span>
                        </label>
                    @endforeach
                    <label class="flex cursor-pointer gap-3 rounded border p-3 text-sm" :class="addressId === '' ? 'border-neutral-900' : 'border-neutral-200'">
                        <input type="radio" name="saved_address_id" value="" x-model="addressId">
                        آدرس جدید
                    </label>
                </fieldset>
            @endif

            <div class="space-y-3" x-show="addressId === ''">
                <div class="grid gap-3 sm:grid-cols-2">
                    <input name="province" placeholder="استان *" value="{{ old('province') }}" :required="addressId === ''" aria-label="استان"
                           class="rounded border border-neutral-300 px-3 py-2 text-sm">
                    <input name="city" placeholder="شهر *" value="{{ old('city') }}" :required="addressId === ''" aria-label="شهر"
                           class="rounded border border-neutral-300 px-3 py-2 text-sm">
                </div>
                <textarea name="address_line" placeholder="آدرس کامل *" rows="3" :required="addressId === ''" aria-label="آدرس کامل"
                          class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">{{ old('address_line') }}</textarea>
                <input name="postal_code" placeholder="کد پستی (اختیاری)" value="{{ old('postal_code') }}" dir="ltr" aria-label="کد پستی"
                       class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
                @if ($customer)
                    <label class="flex items-center gap-2 text-sm">
                        <input type="hidden" name="save_address" value="0">
                        <input type="checkbox" name="save_address" value="1" @checked(old('save_address', '1') === '1')>
                        این آدرس در دفترچه آدرس من ذخیره شود
                    </label>
                @endif
            </div>
        </div>

        <p class="text-xs text-neutral-500">
            پرداخت در این سفارش به‌صورت آنلاین نیست — همکاران ما پس از ثبت سفارش برای هماهنگی پرداخت و ارسال با شما تماس می‌گیرند.
            @if ($shipping->method === \App\Services\Checkout\ShippingMethod::Announced)
                هزینه ارسال جداگانه محاسبه می‌شود و در همان تماس به شما اعلام خواهد شد.
            @elseif ($shipping->method === \App\Services\Checkout\ShippingMethod::CashOnDelivery)
                ارسال به‌صورت پس‌کرایه است و هزینه‌ی آن را هنگام تحویل به باربری پرداخت می‌کنید.
            @endif
        </p>

        <button type="submit" class="w-full rounded-full bg-brand-accent px-6 py-3 text-sm font-semibold text-white hover:bg-brand-accent-dark">
            ثبت سفارش
        </button>
    </form>
</div>
@endsection
