@extends('account.layout')

@section('title', 'پیام جدید')

@section('account-content')
<h1 class="mb-6 text-xl font-bold text-neutral-900">پیام جدید به کارشناس</h1>

@if ($errors->any())
    <div class="mb-6 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<form method="POST" action="{{ route('account.conversations.store', array_filter(['order' => $order?->id, 'quote_request' => $quoteRequest?->id])) }}"
      enctype="multipart/form-data" class="space-y-4 rounded-lg border border-neutral-200 bg-white p-6">
    @csrf

    @if ($order || $quoteRequest)
        <p class="rounded bg-neutral-50 p-3 text-xs text-neutral-600">
            این پیام درباره‌ی {{ $order ? 'سفارش '.$order->order_number : 'استعلام قیمت «'.($quoteRequest->product?->name ?? 'درخواست عمومی').'»' }} است.
        </p>
    @endif

    <div>
        <label for="subject" class="mb-1 block text-sm font-medium">موضوع</label>
        <input id="subject" name="subject" value="{{ old('subject', $subject) }}" required maxlength="255"
               class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
    </div>

    @include('conversations.partials.compose')

    <button type="submit" class="rounded-full bg-brand-accent px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-accent-dark">ارسال پیام</button>
</form>
@endsection
