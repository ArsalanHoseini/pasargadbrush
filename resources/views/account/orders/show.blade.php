@extends('account.layout')

@section('title', 'سفارش '.$order->order_number)

@section('account-content')
<div class="mb-6 flex flex-wrap items-baseline justify-between gap-2">
    <h1 class="text-xl font-bold text-neutral-900">سفارش <span dir="ltr">{{ $order->order_number }}</span></h1>
    <div class="flex items-center gap-4">
        @if ($order->items->contains(fn ($item) => $item->product_variant_id !== null))
            <form method="POST" action="{{ route('account.orders.reorder', $order) }}">
                @csrf
                <button type="submit" class="rounded-full bg-brand-accent px-4 py-2 text-xs font-semibold text-white hover:bg-brand-accent-dark">سفارش مجدد</button>
            </form>
        @endif
        <a href="{{ route('account.orders.index') }}" class="text-sm text-brand-accent-dark hover:underline">بازگشت به سفارش‌ها</a>
    </div>
</div>

<div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-neutral-200 bg-white p-4 text-sm">
    <span>سؤالی درباره‌ی این سفارش دارید یا می‌خواهید فایلی بفرستید؟</span>
    <a href="{{ $order->conversation ? route('account.conversations.show', $order->conversation) : route('account.conversations.create', ['order' => $order->id]) }}"
       class="rounded-full bg-neutral-900 px-4 py-2 text-xs font-semibold text-white hover:bg-neutral-700">
        {{ $order->conversation ? 'مشاهده گفتگو با کارشناس' : 'پیام به کارشناس درباره‌ی این سفارش' }}
    </a>
</div>

@include('orders.partials.details')
@endsection
