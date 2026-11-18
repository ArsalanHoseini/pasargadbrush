@extends('account.layout')

@section('title', 'حساب کاربری')

@section('account-content')
<h1 class="mb-6 text-xl font-bold text-neutral-900">حساب کاربری</h1>

@if ($errors->any())
    <div class="mb-6 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<form method="POST" action="{{ route('account.profile.update') }}" class="mb-6 space-y-4 rounded-lg border border-neutral-200 bg-white p-6">
    @csrf
    @method('PUT')
    <h2 class="text-sm font-bold">اطلاعات شما</h2>

    <div class="grid gap-3 sm:grid-cols-2">
        <div>
            <label for="name" class="mb-1 block text-sm font-medium">نام و نام خانوادگی</label>
            <input id="name" name="name" value="{{ old('name', $customer->name) }}" required
                   class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="company_name" class="mb-1 block text-sm font-medium">نام شرکت (اختیاری)</label>
            <input id="company_name" name="company_name" value="{{ old('company_name', $customer->company_name) }}"
                   class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="email" class="mb-1 block text-sm font-medium">ایمیل (اختیاری)</label>
            <input id="email" name="email" type="email" value="{{ old('email', $customer->email) }}" dir="ltr"
                   class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
        </div>
        <div>
            <p class="mb-1 block text-sm font-medium">شماره موبایل</p>
            <p class="rounded border border-neutral-200 bg-neutral-50 px-3 py-2 text-sm text-neutral-600" dir="ltr">{{ $customer->phone }}</p>
        </div>
    </div>

    <button type="submit" class="rounded-full bg-brand-accent-dark px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-accent-darker">
        ذخیره
    </button>
</form>

<form method="POST" action="{{ route('account.profile.password') }}" class="space-y-4 rounded-lg border border-neutral-200 bg-white p-6">
    @csrf
    @method('PUT')
    <h2 class="text-sm font-bold">{{ $customer->hasPassword() ? 'تغییر رمز عبور' : 'تعیین رمز عبور (اختیاری)' }}</h2>
    <p class="text-xs text-neutral-500">
        با تعیین رمز عبور، می‌توانید بدون کد پیامکی و با شماره موبایل و رمز وارد شوید. ورود با کد پیامکی همیشه فعال می‌ماند.
    </p>

    <div class="grid gap-3 sm:grid-cols-2">
        <div>
            <label for="password" class="mb-1 block text-sm font-medium">رمز عبور جدید (حداقل ۸ کاراکتر)</label>
            <input id="password" name="password" type="password" autocomplete="new-password" required dir="ltr"
                   class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="password_confirmation" class="mb-1 block text-sm font-medium">تکرار رمز عبور</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required dir="ltr"
                   class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
        </div>
    </div>

    <button type="submit" class="rounded-full bg-neutral-900 px-6 py-2.5 text-sm font-semibold text-white hover:bg-neutral-700">
        ذخیره رمز عبور
    </button>
</form>
@endsection
