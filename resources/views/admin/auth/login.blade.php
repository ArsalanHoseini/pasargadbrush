@extends('admin.layout')

@section('title', 'ورود به مدیریت')

@section('content')
<div class="flex items-center justify-center py-12">
    <div class="w-full max-w-sm rounded-lg border border-neutral-200 bg-white p-8 shadow-sm">
        <h1 class="mb-6 text-center text-lg font-semibold">ورود به پنل مدیریت</h1>

        @if ($errors->any())
            <div class="mb-4 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login') }}" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="admin-label">ایمیل</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                       class="admin-input">
            </div>
            <div>
                <label for="password" class="admin-label">رمز عبور</label>
                <input id="password" name="password" type="password" required
                       class="admin-input">
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="remember">
                مرا به خاطر بسپار
            </label>
            <button type="submit"
                    class="w-full inline-flex items-center gap-1.5 rounded-lg bg-brand-accent-dark px-5 py-2 text-sm font-medium text-white hover:bg-brand-accent-darker">
                ورود
            </button>
        </form>
    </div>
</div>
@endsection
