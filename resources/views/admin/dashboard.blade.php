@extends('admin.layout')

@section('title', 'داشبورد')

@section('content')
<div class="min-h-screen p-6">
    <header class="mb-6 flex items-center justify-between">
        <h1 class="text-lg font-semibold">داشبورد مدیریت</h1>
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="text-sm text-neutral-600 hover:text-neutral-900">خروج</button>
        </form>
    </header>

    <p class="text-sm text-neutral-500">
        خوش آمدید، {{ auth()->user()->name }}. این داشبورد موقت است — نسخه کامل (محصولات، سفارش‌ها، محتوا) در فاز ۶ تکمیل می‌شود.
    </p>
</div>
@endsection
