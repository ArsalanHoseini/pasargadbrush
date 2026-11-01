@extends('account.layout')

@section('title', $address->exists ? 'ویرایش آدرس' : 'آدرس جدید')

@section('account-content')
<h1 class="mb-6 text-xl font-bold text-neutral-900">{{ $address->exists ? 'ویرایش آدرس' : 'آدرس جدید' }}</h1>

@if ($errors->any())
    <div class="mb-6 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<form method="POST"
      action="{{ $address->exists ? route('account.addresses.update', $address) : route('account.addresses.store') }}"
      class="space-y-4 rounded-lg border border-neutral-200 bg-white p-6">
    @csrf
    @if ($address->exists) @method('PUT') @endif

    <div>
        <label for="label" class="mb-1 block text-sm font-medium">عنوان (اختیاری — مثلاً «کارخانه» یا «دفتر مرکزی»)</label>
        <input id="label" name="label" value="{{ old('label', $address->label) }}"
               class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
    </div>
    <div class="grid gap-3 sm:grid-cols-2">
        <div>
            <label for="recipient_name" class="mb-1 block text-sm font-medium">نام گیرنده</label>
            <input id="recipient_name" name="recipient_name" value="{{ old('recipient_name', $address->recipient_name) }}" required
                   class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="recipient_phone" class="mb-1 block text-sm font-medium">تلفن گیرنده</label>
            <input id="recipient_phone" name="recipient_phone" type="tel" value="{{ old('recipient_phone', $address->recipient_phone) }}" required dir="ltr"
                   class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="province" class="mb-1 block text-sm font-medium">استان</label>
            <input id="province" name="province" value="{{ old('province', $address->province) }}" required
                   class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="city" class="mb-1 block text-sm font-medium">شهر</label>
            <input id="city" name="city" value="{{ old('city', $address->city) }}" required
                   class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
        </div>
    </div>
    <div>
        <label for="address_line" class="mb-1 block text-sm font-medium">آدرس کامل</label>
        <textarea id="address_line" name="address_line" rows="3" required
                  class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">{{ old('address_line', $address->address_line) }}</textarea>
    </div>
    <div class="sm:w-1/2">
        <label for="postal_code" class="mb-1 block text-sm font-medium">کد پستی (اختیاری)</label>
        <input id="postal_code" name="postal_code" value="{{ old('postal_code', $address->postal_code) }}" dir="ltr"
               class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
    </div>
    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $address->is_default))>
        آدرس پیش‌فرض برای خرید
    </label>

    <div class="flex items-center gap-4">
        <button type="submit" class="rounded-full bg-brand-accent px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-accent-dark">ذخیره</button>
        <a href="{{ route('account.addresses.index') }}" class="text-sm text-neutral-500 hover:text-neutral-900">انصراف</a>
    </div>
</form>
@endsection
