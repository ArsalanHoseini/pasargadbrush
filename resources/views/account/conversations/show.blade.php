@extends('account.layout')

@section('title', $conversation->subject)

@section('account-content')
<div class="mb-6 flex flex-wrap items-baseline justify-between gap-2">
    <div>
        <h1 class="text-xl font-bold text-neutral-900">{{ $conversation->subject }}</h1>
        <p class="mt-1 text-xs text-neutral-500">
            {{ \App\Models\Conversation::CUSTOMER_STATUS_LABELS[$conversation->status] }}
            @if ($conversation->order)
                — <a href="{{ route('account.orders.show', $conversation->order) }}" class="text-brand-accent-dark hover:underline">مشاهده سفارش <span dir="ltr">{{ $conversation->order->order_number }}</span></a>
            @elseif ($conversation->quoteRequest)
                — <a href="{{ route('account.quote-requests.index') }}" class="text-brand-accent-dark hover:underline">استعلام قیمت {{ $conversation->quoteRequest->product?->name }}</a>
            @endif
        </p>
    </div>
    <a href="{{ route('account.conversations.index') }}" class="text-sm text-brand-accent-dark hover:underline">همه پیام‌ها</a>
</div>

@include('conversations.partials.thread', ['viewer' => 'customer', 'attachmentRoute' => 'account.conversations.attachment'])

@if ($errors->any())
    <div class="mt-6 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<form method="POST" action="{{ route('account.conversations.reply', $conversation) }}" enctype="multipart/form-data"
      class="mt-6 space-y-3 rounded-lg border border-neutral-200 bg-white p-5">
    @csrf
    @if ($conversation->status === \App\Models\Conversation::STATUS_CLOSED)
        <p class="text-xs text-neutral-500">این گفتگو بسته شده است؛ با ارسال پیام جدید دوباره باز می‌شود.</p>
    @endif
    @include('conversations.partials.compose', ['placeholder' => 'پاسخ خود را بنویسید…'])
    <button type="submit" class="rounded-full bg-brand-accent px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-accent-dark">ارسال</button>
</form>
@endsection
