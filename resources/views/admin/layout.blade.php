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
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-y-2">
                <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm">
                    <a href="{{ route('admin.dashboard') }}" class="font-semibold">پاسارگاد برس | مدیریت</a>
                    <a href="{{ route('admin.dashboard') }}" class="text-neutral-600 hover:text-neutral-900">داشبورد</a>
                    <a href="{{ route('admin.media.index') }}" class="text-neutral-600 hover:text-neutral-900">کتابخانه مدیا</a>
                    <a href="{{ route('admin.categories.index') }}" class="text-neutral-600 hover:text-neutral-900">دسته‌بندی‌ها</a>
                    <a href="{{ route('admin.products.index') }}" class="text-neutral-600 hover:text-neutral-900">محصولات</a>
                    <a href="{{ route('admin.blog-categories.index') }}" class="text-neutral-600 hover:text-neutral-900">دسته‌بندی بلاگ</a>
                    <a href="{{ route('admin.blog-posts.index') }}" class="text-neutral-600 hover:text-neutral-900">مقالات</a>
                    <a href="{{ route('admin.comments.index') }}" class="text-neutral-600 hover:text-neutral-900">
                        دیدگاه‌ها
                        @php($pendingCommentCount = \App\Models\Comment::where('status', 'pending')->count())
                        @if ($pendingCommentCount > 0)
                            <span class="mr-1 rounded-full bg-brand-accent px-1.5 py-0.5 text-xs text-white">{{ $pendingCommentCount }}</span>
                        @endif
                    </a>
                    <a href="{{ route('admin.import.index') }}" class="text-neutral-600 hover:text-neutral-900">ایمپورت وردپرس</a>
                    <a href="{{ route('admin.quote-requests.index') }}" class="text-neutral-600 hover:text-neutral-900">
                        درخواست‌های استعلام
                        @php($newCount = \App\Models\QuoteRequest::where('status', 'new')->count())
                        @if ($newCount > 0)
                            <span class="mr-1 rounded-full bg-brand-accent px-1.5 py-0.5 text-xs text-white">{{ $newCount }}</span>
                        @endif
                    </a>
                    <a href="{{ route('admin.orders.index') }}" class="text-neutral-600 hover:text-neutral-900">سفارش‌ها</a>
                    <a href="{{ route('admin.customers.index') }}" class="text-neutral-600 hover:text-neutral-900">مشتریان</a>
                    <a href="{{ route('admin.pages.index') }}" class="text-neutral-600 hover:text-neutral-900">صفحات ثابت</a>
                    <a href="{{ route('admin.settings.edit') }}" class="text-neutral-600 hover:text-neutral-900">تنظیمات</a>
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
