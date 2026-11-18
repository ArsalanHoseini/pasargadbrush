@extends('admin.layout')

@section('title', 'حساب من')

@php
$inputClass = 'w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm focus:border-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-accent/20';
@endphp

@section('content')
<x-admin.page-header title="حساب من" :description="'نقش: '.$user->roleLabel()" />

<div class="grid max-w-4xl gap-6 lg:grid-cols-2">
    <form method="POST" action="{{ route('admin.profile.update') }}" class="space-y-4 rounded-xl border border-neutral-200 bg-white p-6">
        @csrf @method('PUT')
        <h2 class="font-semibold">مشخصات</h2>
        <div>
            <label for="name" class="admin-label">نام</label>
            <input id="name" name="name" value="{{ old('name', $user->name) }}" required maxlength="100" class="{{ $inputClass }}">
            @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="email" class="admin-label">ایمیل (برای ورود)</label>
            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required dir="ltr" class="{{ $inputClass }}">
            @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <x-admin.button>ذخیره</x-admin.button>
    </form>

    <form method="POST" action="{{ route('admin.profile.password') }}" class="space-y-4 rounded-xl border border-neutral-200 bg-white p-6">
        @csrf @method('PUT')
        <h2 class="font-semibold">تغییر رمز عبور</h2>
        <div>
            <label for="current_password" class="admin-label">رمز عبور فعلی</label>
            <input id="current_password" name="current_password" type="password" required autocomplete="current-password" dir="ltr" class="{{ $inputClass }}">
            @error('current_password', 'password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password" class="admin-label">رمز عبور جدید</label>
            <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password" dir="ltr" class="{{ $inputClass }}">
            @error('password', 'password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password_confirmation" class="admin-label">تکرار رمز عبور جدید</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" dir="ltr" class="{{ $inputClass }}">
        </div>
        <x-admin.button>تغییر رمز عبور</x-admin.button>
    </form>
</div>
@endsection
