@extends('layouts.public')

@section('title', 'کد ورود')
@section('robots', 'noindex, follow')

@section('content')
<div class="mx-auto max-w-md px-4 py-12 sm:px-6">
    <h1 class="mb-2 text-2xl font-bold text-neutral-900">کد ورود را وارد کنید</h1>
    <p class="mb-6 text-sm text-neutral-600">
        کد {{ \App\Services\CustomerAuth\LoginCodeManager::CODE_LENGTH }} رقمی به شماره‌ی
        <span dir="ltr" class="font-medium">{{ $phone }}</span>
        پیامک شد.
        <a href="{{ route('account.login') }}" class="text-brand-accent-dark hover:underline">تغییر شماره</a>
    </p>

    @if (session('status'))
        <div class="mb-6 rounded border border-green-200 bg-green-50 p-3 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('account.login.verify.store') }}" class="space-y-4 rounded-lg border border-neutral-200 bg-white p-6">
        @csrf
        <div>
            <label for="code" class="mb-1 block text-sm font-medium text-neutral-800">کد ورود</label>
            <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" required autofocus dir="ltr"
                   maxlength="10" class="w-full rounded border border-neutral-300 px-3 py-3 text-center text-xl tracking-[0.5em]">
        </div>
        <button type="submit" class="w-full rounded-full bg-brand-accent px-6 py-3 text-sm font-semibold text-white hover:bg-brand-accent-dark">
            ورود
        </button>
    </form>

    <form method="POST" action="{{ route('account.login.code') }}" class="mt-4 text-center"
          x-data="{ wait: {{ $resendAfterSeconds }} }" x-init="const timer = setInterval(() => { wait > 0 ? wait-- : clearInterval(timer) }, 1000)">
        @csrf
        <input type="hidden" name="phone" value="{{ $phone }}">
        <button type="submit" :disabled="wait > 0" class="text-sm text-brand-accent-dark hover:underline disabled:cursor-not-allowed disabled:text-neutral-400 disabled:no-underline">
            ارسال دوباره‌ی کد<span x-show="wait > 0" x-text="` (${wait} ثانیه)`"></span>
        </button>
    </form>
</div>
@endsection
