@extends('layouts.public')

@section('title', 'پیگیری سفارش')
@section('meta_description', 'پیگیری وضعیت سفارش با شماره سفارش و شماره موبایل')

@section('content')
<div class="mx-auto max-w-md px-4 py-12 sm:px-6">
    <h1 class="mb-2 text-2xl font-bold text-neutral-900">پیگیری سفارش</h1>
    <p class="mb-6 text-sm text-neutral-600">
        شماره سفارش (در پیامک و ایمیل تأیید سفارش آمده است) و شماره موبایلی را که هنگام ثبت سفارش وارد کردید بنویسید.
    </p>

    @if ($errors->any())
        <div class="mb-6 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('orders.track.store') }}" class="space-y-4 rounded-lg border border-neutral-200 bg-white p-6">
        @csrf

        <div>
            <label for="order_number" class="mb-1 block text-sm font-medium text-neutral-800">شماره سفارش</label>
            <input id="order_number" name="order_number" value="{{ old('order_number') }}" required dir="ltr"
                   placeholder="PB-20261031-ABC123" autocomplete="off"
                   class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
        </div>

        <div>
            <label for="phone" class="mb-1 block text-sm font-medium text-neutral-800">شماره موبایل</label>
            <input id="phone" name="phone" type="tel" inputmode="tel" value="{{ old('phone') }}" required dir="ltr"
                   placeholder="۰۹۱۲۱۲۳۴۵۶۷" autocomplete="tel"
                   class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
        </div>

        <button type="submit" class="w-full rounded-full bg-brand-accent px-6 py-3 text-sm font-semibold text-white hover:bg-brand-accent-dark">
            مشاهده وضعیت سفارش
        </button>
    </form>

    @if (setting('contact_mobile'))
        <p class="mt-6 text-center text-xs text-neutral-500">
            شماره سفارش را ندارید؟ با
            <a href="tel:{{ setting('contact_mobile') }}" class="text-brand-accent-dark hover:underline" dir="ltr">{{ setting('contact_mobile') }}</a>
            تماس بگیرید.
        </p>
    @endif
</div>
@endsection
