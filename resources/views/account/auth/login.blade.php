@extends('layouts.public')

@section('title', 'ورود / ثبت‌نام')

@section('content')
<div class="mx-auto max-w-md px-4 py-12 sm:px-6" x-data="{ tab: @js($errors->has('password') || old('login_method') === 'password' ? 'password' : 'code') }">
    <h1 class="mb-2 text-2xl font-bold text-neutral-900">ورود / ثبت‌نام</h1>
    <p class="mb-6 text-sm text-neutral-600">
        با شماره موبایل وارد شوید تا سفارش‌ها، استعلام‌ها و پیام‌های خود را یک‌جا ببینید. اگر قبلاً با این شماره خرید کرده‌اید، سفارش‌هایتان در حساب شما خواهد بود.
    </p>

    @if ($errors->any())
        <div class="mb-6 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="mb-4 grid grid-cols-2 rounded-full bg-neutral-100 p-1 text-sm" role="tablist">
        <button type="button" role="tab" @click="tab = 'code'" :aria-selected="tab === 'code'"
                :class="tab === 'code' ? 'bg-white shadow font-semibold' : 'text-neutral-600'" class="rounded-full py-2">ورود با کد پیامکی</button>
        <button type="button" role="tab" @click="tab = 'password'" :aria-selected="tab === 'password'"
                :class="tab === 'password' ? 'bg-white shadow font-semibold' : 'text-neutral-600'" class="rounded-full py-2">ورود با رمز عبور</button>
    </div>

    <form method="POST" action="{{ route('account.login.code') }}" x-show="tab === 'code'"
          class="space-y-4 rounded-lg border border-neutral-200 bg-white p-6">
        @csrf
        <div>
            <label for="phone-code" class="mb-1 block text-sm font-medium text-neutral-800">شماره موبایل</label>
            <input id="phone-code" name="phone" type="tel" inputmode="tel" autocomplete="tel" required dir="ltr"
                   value="{{ old('phone') }}" placeholder="۰۹۱۲۱۲۳۴۵۶۷"
                   class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
        </div>
        <button type="submit" class="w-full rounded-full bg-brand-accent px-6 py-3 text-sm font-semibold text-white hover:bg-brand-accent-dark">
            دریافت کد ورود
        </button>
        <p class="text-xs text-neutral-500">اگر حساب ندارید، با همین کد حساب شما ساخته می‌شود.</p>
    </form>

    <form method="POST" action="{{ route('account.login.password') }}" x-show="tab === 'password'" x-cloak
          class="space-y-4 rounded-lg border border-neutral-200 bg-white p-6">
        @csrf
        <input type="hidden" name="login_method" value="password">
        <div>
            <label for="phone-password" class="mb-1 block text-sm font-medium text-neutral-800">شماره موبایل</label>
            <input id="phone-password" name="phone" type="tel" inputmode="tel" autocomplete="username" required dir="ltr"
                   value="{{ old('phone') }}" placeholder="۰۹۱۲۱۲۳۴۵۶۷"
                   class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="password" class="mb-1 block text-sm font-medium text-neutral-800">رمز عبور</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required dir="ltr"
                   class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
        </div>
        <button type="submit" class="w-full rounded-full bg-neutral-900 px-6 py-3 text-sm font-semibold text-white hover:bg-neutral-700">
            ورود
        </button>
        <p class="text-xs text-neutral-500">
            رمز عبور را فراموش کرده‌اید یا هنوز تعریف نکرده‌اید؟ با کد پیامکی وارد شوید و از «حساب کاربری» رمز جدید تعیین کنید.
        </p>
    </form>
</div>
@endsection
