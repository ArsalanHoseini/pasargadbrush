@extends('layouts.public')

@section('title', 'وضعیت سفارش '.$order->order_number)

@section('content')
<div class="mx-auto max-w-2xl px-4 py-10 sm:px-6">
    <div class="mb-6 flex flex-wrap items-baseline justify-between gap-2">
        <h1 class="text-2xl font-bold text-neutral-900">وضعیت سفارش</h1>
        <a href="{{ route('orders.track.create') }}" class="text-sm text-brand-accent-dark hover:underline">پیگیری سفارش دیگر</a>
    </div>

    @include('orders.partials.details')

    @if (setting('contact_mobile'))
        <p class="text-center text-sm text-neutral-500">
            سؤالی درباره‌ی سفارش دارید؟ با
            <a href="tel:{{ setting('contact_mobile') }}" class="text-brand-accent-dark hover:underline" dir="ltr">{{ setting('contact_mobile') }}</a>
            تماس بگیرید.
        </p>
    @endif
</div>
@endsection
