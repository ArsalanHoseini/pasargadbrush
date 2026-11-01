@extends('admin.layout')

@section('title', 'تنظیمات ارسال')

@php
    use App\Services\Checkout\ShippingMethod;

    $descriptions = [
        ShippingMethod::Announced->value => 'مبلغی محاسبه نمی‌شود. پس از تماس با مشتری، هزینه‌ی ارسال را در صفحه‌ی همان سفارش وارد کنید تا جمع کل به‌روز شود.',
        ShippingMethod::CashOnDelivery->value => 'مشتری هزینه‌ی ارسال را هنگام تحویل به باربری/پست می‌پردازد. به مبلغ سفارش چیزی اضافه نمی‌شود.',
        ShippingMethod::Flat->value => 'یک مبلغ ثابت به هر سفارش اضافه می‌شود — در صورت تمایل، برای خریدهای بالای یک مبلغ رایگان.',
    ];
@endphp

@section('content')
<h1 class="mb-2 text-lg font-semibold">تنظیمات ارسال</h1>
<p class="mb-6 text-sm text-neutral-500">
    تغییر این تنظیمات فقط روی سفارش‌های جدید اثر دارد؛ سفارش‌های قبلی با همان روشی که ثبت شده‌اند باقی می‌مانند.
</p>

@if ($errors->any())
    <div class="mb-6 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<form method="POST" action="{{ route('admin.shipping.update') }}" class="max-w-2xl space-y-6"
      x-data="{ method: @js(old('shipping_method', $method->value)) }">
    @csrf
    @method('PUT')

    <div class="rounded-lg border border-neutral-200 bg-white p-6">
        <h2 class="mb-4 text-sm font-medium text-neutral-500">روش محاسبه هزینه ارسال</h2>
        <div class="space-y-3">
            @foreach (ShippingMethod::cases() as $case)
                <label class="flex cursor-pointer gap-3 rounded border border-neutral-200 p-3 hover:bg-neutral-50"
                       :class="method === @js($case->value) && 'border-neutral-900 bg-neutral-50'">
                    <input type="radio" name="shipping_method" value="{{ $case->value }}" x-model="method" class="mt-1">
                    <span>
                        <span class="block text-sm font-medium">{{ $case->label() }}</span>
                        <span class="block text-xs text-neutral-500">{{ $descriptions[$case->value] }}</span>
                    </span>
                </label>
            @endforeach
        </div>
    </div>

    <div class="rounded-lg border border-neutral-200 bg-white p-6" x-show="method === @js(ShippingMethod::Flat->value)" x-cloak>
        <h2 class="mb-4 text-sm font-medium text-neutral-500">مبلغ ثابت</h2>
        <div class="space-y-4">
            <div>
                <label for="flat_rate_toman" class="mb-1 block text-sm font-medium">هزینه ارسال هر سفارش (تومان)</label>
                <input id="flat_rate_toman" name="flat_rate_toman" type="number" min="1" dir="ltr"
                       value="{{ old('flat_rate_toman', $flatRateToman) }}"
                       class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label for="free_threshold_toman" class="mb-1 block text-sm font-medium">ارسال رایگان برای خریدهای بالای (تومان) — اختیاری</label>
                <input id="free_threshold_toman" name="free_threshold_toman" type="number" min="1" dir="ltr"
                       value="{{ old('free_threshold_toman', $freeThresholdToman) }}"
                       class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-neutral-500">خالی بگذارید تا ارسال رایگان نداشته باشید.</p>
            </div>
        </div>
    </div>

    <button type="submit" class="rounded bg-neutral-900 px-4 py-2 text-sm text-white hover:bg-neutral-700">
        ذخیره تنظیمات ارسال
    </button>
</form>
@endsection
