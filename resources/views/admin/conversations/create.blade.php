@extends('admin.layout')

@section('title', 'پیام جدید به مشتری')

@section('content')
<h1 class="mb-6 text-lg font-semibold">پیام جدید به مشتری</h1>

@if ($errors->any())
    <div class="mb-6 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<form method="POST"
      action="{{ route('admin.conversations.store', array_filter(['order' => $order?->id, 'quote_request' => $quoteRequest?->id, 'customer' => ! $order && ! $quoteRequest ? $customer->id : null])) }}"
      enctype="multipart/form-data" class="max-w-2xl space-y-4 rounded-lg border border-neutral-200 bg-white p-6">
    @csrf

    <p class="rounded bg-neutral-50 p-3 text-sm">
        به: <strong>{{ $customer->name ?: 'مشتری' }}</strong> <span dir="ltr">{{ $customer->phone }}</span>
        <span class="block text-xs text-neutral-500">
            مشتری با پیامک (و ایمیل، اگر ثبت کرده باشد) مطلع می‌شود و پیام را در حساب کاربری خود می‌بیند.
            @unless ($customer->exists)
                این مشتری هنوز حساب ندارد؛ با اولین ورود با همین شماره، پیام را خواهد دید.
            @endunless
        </span>
    </p>

    <div>
        <label for="subject" class="mb-1 block text-sm font-medium">موضوع</label>
        <input id="subject" name="subject" value="{{ old('subject', $subject) }}" required maxlength="255"
               class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
    </div>

    @include('conversations.partials.compose')

    <button type="submit" class="rounded bg-neutral-900 px-4 py-2 text-sm text-white hover:bg-neutral-700">ارسال پیام</button>
</form>
@endsection
