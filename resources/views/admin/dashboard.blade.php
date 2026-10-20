@extends('admin.layout')

@section('title', 'داشبورد')

@section('content')
<h1 class="mb-4 text-lg font-semibold">داشبورد مدیریت</h1>

<p class="text-sm text-neutral-500">
    خوش آمدید، {{ auth()->user()->name }}. این داشبورد موقت است — نسخه کامل (محصولات، سفارش‌ها، محتوا) در فاز ۶ تکمیل می‌شود.
    برای آپلود دستی فایل به <a href="{{ route('admin.media.index') }}" class="text-neutral-900 underline">کتابخانه مدیا</a> بروید.
</p>
@endsection
