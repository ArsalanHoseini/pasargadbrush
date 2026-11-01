@extends('account.layout')

@section('title', 'سفارش‌های من')

@section('account-content')
<h1 class="mb-6 text-xl font-bold text-neutral-900">سفارش‌های من</h1>

<div class="rounded-lg border border-neutral-200 bg-white p-5">
    @include('account.orders.partials.table')
</div>

<div class="mt-6">{{ $orders->links() }}</div>
@endsection
