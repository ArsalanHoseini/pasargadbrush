<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'مدیریت') — پاسارگاد برس</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-neutral-50 text-neutral-900 antialiased">
    @auth
        <nav class="border-b border-neutral-200 bg-white px-6 py-3">
            <div class="mx-auto flex max-w-5xl items-center justify-between">
                <div class="flex items-center gap-6 text-sm">
                    <a href="{{ route('admin.dashboard') }}" class="font-semibold">پاسارگاد برس | مدیریت</a>
                    <a href="{{ route('admin.dashboard') }}" class="text-neutral-600 hover:text-neutral-900">داشبورد</a>
                    <a href="{{ route('admin.media.index') }}" class="text-neutral-600 hover:text-neutral-900">کتابخانه مدیا</a>
                    <a href="{{ route('admin.categories.index') }}" class="text-neutral-600 hover:text-neutral-900">دسته‌بندی‌ها</a>
                    <a href="{{ route('admin.products.index') }}" class="text-neutral-600 hover:text-neutral-900">محصولات</a>
                    <a href="{{ route('admin.blog-categories.index') }}" class="text-neutral-600 hover:text-neutral-900">دسته‌بندی بلاگ</a>
                    <a href="{{ route('admin.blog-posts.index') }}" class="text-neutral-600 hover:text-neutral-900">مقالات</a>
                    <a href="{{ route('admin.import.index') }}" class="text-neutral-600 hover:text-neutral-900">ایمپورت وردپرس</a>
                </div>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="text-sm text-neutral-600 hover:text-neutral-900">خروج</button>
                </form>
            </div>
        </nav>
    @endauth

    <main class="mx-auto max-w-5xl px-6 py-8">
        @if (session('status'))
            <div class="mb-6 rounded border border-green-200 bg-green-50 p-3 text-sm text-green-700">
                {{ session('status') }}
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
