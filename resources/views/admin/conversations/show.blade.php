@extends('admin.layout')

@section('title', $conversation->subject)

@section('content')
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <h1 class="text-lg font-semibold">{{ $conversation->subject }}</h1>
        <p class="mt-1 text-sm text-neutral-600">
            <a href="{{ route('admin.customers.show', $conversation->customer) }}" class="hover:underline">{{ $conversation->customer->name ?: 'مشتری' }}</a>
            <span dir="ltr">{{ $conversation->customer->phone }}</span>
            — {{ \App\Models\Conversation::STAFF_STATUS_LABELS[$conversation->status] }}
            @if ($conversation->order)
                — <a href="{{ route('admin.orders.edit', $conversation->order) }}" class="text-blue-700 hover:underline">سفارش <span dir="ltr">{{ $conversation->order->order_number }}</span></a>
            @elseif ($conversation->quoteRequest)
                — <a href="{{ route('admin.quote-requests.edit', $conversation->quoteRequest) }}" class="text-blue-700 hover:underline">استعلام #{{ $conversation->quoteRequest->id }}</a>
            @endif
        </p>
    </div>

    <form method="POST" action="{{ route('admin.conversations.status', $conversation) }}">
        @csrf
        @method('PUT')
        @if ($conversation->status === \App\Models\Conversation::STATUS_CLOSED)
            <input type="hidden" name="status" value="open">
            <button type="submit" class="rounded border border-neutral-300 px-3 py-1.5 text-sm hover:bg-neutral-50">باز کردن دوباره</button>
        @else
            <input type="hidden" name="status" value="closed">
            <button type="submit" class="rounded border border-neutral-300 px-3 py-1.5 text-sm hover:bg-neutral-50">بستن گفتگو</button>
        @endif
    </form>
</div>

<div class="max-w-3xl">
    @include('conversations.partials.thread', ['viewer' => 'staff', 'attachmentRoute' => 'admin.conversations.attachment'])

    @if ($errors->any())
        <div class="mt-6 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('admin.conversations.reply', $conversation) }}" enctype="multipart/form-data"
          class="mt-6 space-y-3 rounded-lg border border-neutral-200 bg-white p-5">
        @csrf
        @include('conversations.partials.compose', ['placeholder' => 'پاسخ به مشتری…'])
        <button type="submit" class="rounded bg-neutral-900 px-4 py-2 text-sm text-white hover:bg-neutral-700">ارسال پاسخ</button>
        <p class="text-xs text-neutral-500">مشتری با پیامک (و ایمیل، اگر ثبت کرده باشد) از پاسخ مطلع می‌شود.</p>
    </form>
</div>
@endsection
