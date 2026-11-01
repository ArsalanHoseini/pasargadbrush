@extends('account.layout')

@section('title', 'آدرس‌ها')

@section('account-content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-2">
    <h1 class="text-xl font-bold text-neutral-900">آدرس‌ها</h1>
    <a href="{{ route('account.addresses.create') }}" class="rounded-full bg-brand-accent px-5 py-2 text-sm font-semibold text-white hover:bg-brand-accent-dark">+ آدرس جدید</a>
</div>

<p class="mb-4 text-xs text-neutral-500">
    هنگام خرید می‌توانید یکی از این آدرس‌ها را انتخاب کنید. تغییر یا حذف آدرس، روی سفارش‌های قبلی اثری ندارد.
</p>

<div class="grid gap-4 sm:grid-cols-2">
    @forelse ($addresses as $address)
        <div class="rounded-lg border bg-white p-5 text-sm {{ $address->is_default ? 'border-neutral-900' : 'border-neutral-200' }}">
            <div class="mb-2 flex items-center justify-between">
                <p class="font-bold">{{ $address->label ?: 'آدرس' }}</p>
                @if ($address->is_default)
                    <span class="rounded bg-neutral-900 px-2 py-0.5 text-xs text-white">پیش‌فرض</span>
                @endif
            </div>
            <p>{{ $address->province }}، {{ $address->city }}</p>
            <p class="text-neutral-600">{{ $address->address_line }}</p>
            @if ($address->postal_code)
                <p class="text-xs text-neutral-500">کد پستی: <span dir="ltr">{{ $address->postal_code }}</span></p>
            @endif
            <p class="mt-2 text-xs text-neutral-500">گیرنده: {{ $address->recipient_name }} — <span dir="ltr">{{ $address->recipient_phone }}</span></p>

            <div class="mt-4 flex gap-4 text-xs">
                <a href="{{ route('account.addresses.edit', $address) }}" class="text-brand-accent-dark hover:underline">ویرایش</a>
                <form method="POST" action="{{ route('account.addresses.destroy', $address) }}" onsubmit="return confirm('این آدرس حذف شود؟');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-600 hover:underline">حذف</button>
                </form>
            </div>
        </div>
    @empty
        <p class="text-sm text-neutral-500">هنوز آدرسی ذخیره نکرده‌اید.</p>
    @endforelse
</div>
@endsection
