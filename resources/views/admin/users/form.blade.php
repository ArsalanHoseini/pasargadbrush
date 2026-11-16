@extends('admin.layout')

@section('title', $user->exists ? 'ویرایش کاربر' : 'کاربر جدید')

@php
$inputClass = 'w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm focus:border-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-accent/20';
@endphp

@section('content')
<x-admin.page-header :title="$user->exists ? 'ویرایش «'.$user->name.'»' : 'کاربر جدید'" />

<form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}"
      class="max-w-2xl space-y-5 rounded-xl border border-neutral-200 bg-white p-6">
    @csrf
    @if ($user->exists) @method('PUT') @endif

    <div class="grid gap-5 sm:grid-cols-2">
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
    </div>

    <fieldset>
        <legend class="mb-2 text-sm font-medium">نقش</legend>
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach ([
                'admin' => 'دسترسی کامل: سفارش‌ها، مشتریان، پیام‌ها، گزارش‌ها، تنظیمات و کاربران.',
                'editor' => 'فقط محصولات، دسته‌ها، کاربردها، نظرات، بلاگ، صفحات و مدیا.',
            ] as $role => $hint)
                <label class="flex cursor-pointer gap-3 rounded-lg border border-neutral-200 p-3 has-[:checked]:border-brand-accent has-[:checked]:bg-orange-50/50">
                    <input type="radio" name="role" value="{{ $role }}" class="mt-1 accent-brand-accent-dark" @checked(old('role', $user->role) === $role)>
                    <span>
                        <span class="block text-sm font-medium">{{ \App\Models\User::ROLE_LABELS[$role] }}</span>
                        <span class="block text-xs text-neutral-500">{{ $hint }}</span>
                    </span>
                </label>
            @endforeach
        </div>
        @error('role')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </fieldset>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="password" class="admin-label">رمز عبور{{ $user->exists ? ' جدید' : '' }}</label>
            <input id="password" name="password" type="password" autocomplete="new-password" dir="ltr" @required(! $user->exists) minlength="8" class="{{ $inputClass }}">
            <p class="mt-1 text-xs text-neutral-500">{{ $user->exists ? 'برای تغییر ندادن، خالی بگذارید.' : 'حداقل ۸ کاراکتر.' }}</p>
            @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password_confirmation" class="admin-label">تکرار رمز عبور</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" dir="ltr" class="{{ $inputClass }}">
        </div>
    </div>

    <div class="flex items-center gap-3 border-t border-neutral-100 pt-5">
        <x-admin.button>{{ $user->exists ? 'ذخیره تغییرات' : 'ساخت حساب' }}</x-admin.button>
        <a href="{{ route('admin.users.index') }}" class="text-sm text-neutral-500 hover:text-neutral-900">انصراف</a>
    </div>
</form>
@endsection
