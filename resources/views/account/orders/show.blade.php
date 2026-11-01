@extends('account.layout')

@section('title', 'سفارش '.$order->order_number)

@section('account-content')
<div class="mb-6 flex flex-wrap items-baseline justify-between gap-2">
    <h1 class="text-xl font-bold text-neutral-900">سفارش <span dir="ltr">{{ $order->order_number }}</span></h1>
    <a href="{{ route('account.orders.index') }}" class="text-sm text-brand-accent-dark hover:underline">بازگشت به سفارش‌ها</a>
</div>

@include('orders.partials.details')
@endsection
