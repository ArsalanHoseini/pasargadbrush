@extends('admin.layout')

@section('title', 'پیام جدید به مشتری')

@section('content')
<h1 class="mb-6 text-xl font-bold text-neutral-900">پیام جدید به مشتری</h1>

<x-admin.form-errors />

<form method="POST"
      action="{{ route('admin.conversations.store', array_filter(['order' => $order?->id, 'quote_request' => $quoteRequest?->id, 'customer' => ! $order && ! $quoteRequest ? $customer->id : null])) }}"
      enctype="multipart/form-data" class="max-w-2xl space-y-4 admin-card p-6">
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
        <label for="subject" class="admin-label">موضوع</label>
        <input id="subject" name="subject" value="{{ old('subject', $subject) }}" required maxlength="255"
               class="admin-input">
    </div>

    @include('conversations.partials.compose')

    <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-accent-dark px-5 py-2 text-sm font-medium text-white hover:bg-brand-accent-darker">ارسال پیام</button>
</form>
@endsection
