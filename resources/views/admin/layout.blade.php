<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'مدیریت') — پاسارگاد برس</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-neutral-100 text-neutral-900 antialiased" x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">
@auth
    @php
        $navigationGroups = \App\Support\AdminNavigation::groups();
        $currentSection = \App\Support\AdminNavigation::current();
    @endphp

    {{-- Mobile backdrop --}}
    <div x-cloak x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false"
         class="fixed inset-0 z-30 bg-black/40 lg:hidden"></div>

    <aside class="fixed inset-y-0 right-0 z-40 flex w-64 translate-x-full flex-col bg-brand-charcoal text-neutral-300 transition-transform duration-200 lg:translate-x-0"
           :class="sidebarOpen && 'max-lg:translate-x-0'" aria-label="منوی مدیریت">
        <div class="flex h-16 shrink-0 items-center justify-between border-b border-white/10 px-5">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 text-white">
                <span class="flex size-8 items-center justify-center rounded-lg bg-brand-accent text-sm font-bold">پ</span>
                <span class="font-semibold">پاسارگاد برس</span>
            </a>
            <button type="button" class="rounded p-1 hover:bg-white/10 lg:hidden" @click="sidebarOpen = false" aria-label="بستن منو">
                <x-admin.icon name="x" />
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-4 text-sm">
            <a href="{{ route('admin.dashboard') }}"
               @class([
                   'mb-4 flex items-center gap-3 rounded-lg px-3 py-2 transition',
                   'bg-brand-accent text-white' => request()->routeIs('admin.dashboard'),
                   'hover:bg-white/5 hover:text-white' => ! request()->routeIs('admin.dashboard'),
               ])>
                <x-admin.icon name="home" />
                داشبورد
            </a>

            @foreach ($navigationGroups as $group)
                <p class="mb-1 mt-4 px-3 text-xs font-medium text-neutral-500">{{ $group['label'] }}</p>
                <ul class="space-y-0.5">
                    @foreach ($group['items'] as $item)
                        @php($isActive = request()->routeIs($item['active']))
                        <li>
                            <a href="{{ route($item['route']) }}"
                               @if ($isActive) aria-current="page" @endif
                               @class([
                                   'flex items-center gap-3 rounded-lg px-3 py-2 transition',
                                   'bg-white/10 font-medium text-white' => $isActive,
                                   'hover:bg-white/5 hover:text-white' => ! $isActive,
                               ])>
                                <x-admin.icon :name="$item['icon']" @class(['text-brand-accent' => $isActive]) />
                                <span class="flex-1">{{ $item['label'] }}</span>
                                @if ($item['badge'] > 0)
                                    <span class="rounded-full bg-brand-accent px-2 py-0.5 text-xs font-medium text-white">{{ $item['badge'] }}</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </nav>

        <div class="shrink-0 border-t border-white/10 p-3 text-sm">
            <div class="flex items-center gap-3 px-2 py-1">
                <span class="flex size-8 items-center justify-center rounded-full bg-white/10 text-white">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                <a href="{{ route('admin.profile.edit') }}" class="min-w-0 flex-1 rounded hover:text-white" title="حساب من">
                    <p class="truncate text-white">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-neutral-500">{{ auth()->user()->roleLabel() }} — حساب من</p>
                </a>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="rounded p-1.5 hover:bg-white/10 hover:text-white" title="خروج" aria-label="خروج">
                        <x-admin.icon name="logout" />
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <div class="lg:pr-64">
        <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-neutral-200 bg-white/90 px-4 backdrop-blur sm:px-6">
            <button type="button" class="rounded p-1.5 text-neutral-600 hover:bg-neutral-100 lg:hidden" @click="sidebarOpen = true" aria-label="باز کردن منو">
                <x-admin.icon name="menu" />
            </button>

            <nav class="hidden min-w-0 items-center gap-1.5 text-sm text-neutral-500 md:flex" aria-label="مسیر صفحه">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-neutral-900">داشبورد</a>
                @if ($currentSection)
                    <x-admin.icon name="chevron-left" class="size-3.5" />
                    <span>{{ $currentSection['group'] }}</span>
                    <x-admin.icon name="chevron-left" class="size-3.5" />
                    <a href="{{ route($currentSection['route']) }}" class="truncate text-neutral-900 hover:underline">{{ $currentSection['label'] }}</a>
                @endif
            </nav>

            <form method="GET" action="{{ route('admin.search') }}" class="relative mr-auto w-full max-w-sm" role="search">
                <x-admin.icon name="search" class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-neutral-400" />
                <input type="search" name="q" value="{{ request()->routeIs('admin.search') ? request('q') : '' }}"
                       placeholder="{{ auth()->user()->isAdmin() ? 'جستجوی سفارش، محصول، مشتری…' : 'جستجوی محصول…' }}" aria-label="جستجو در پنل"
                       class="w-full rounded-lg border border-neutral-200 bg-neutral-50 py-2 pl-3 pr-9 text-sm focus:border-brand-accent focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-accent/20">
            </form>

            <a href="{{ route('home') }}" target="_blank" rel="noopener"
               class="hidden shrink-0 items-center gap-1.5 rounded-lg px-3 py-2 text-sm text-neutral-600 hover:bg-neutral-100 sm:flex">
                <x-admin.icon name="external" class="size-4" />
                مشاهده سایت
            </a>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:py-8">
            @yield('content')
        </main>
    </div>

    {{-- Toasts --}}
    <div class="pointer-events-none fixed left-4 top-20 z-50 flex w-full max-w-sm flex-col gap-2 px-4 sm:px-0">
        @if (session('status'))
            <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show" x-transition
                 role="status"
                 class="pointer-events-auto flex items-start gap-3 rounded-lg border border-green-200 bg-white p-4 text-sm shadow-lg">
                <x-admin.icon name="check" class="text-green-600" />
                <p class="flex-1">{{ session('status') }}</p>
                <button type="button" @click="show = false" class="text-neutral-400 hover:text-neutral-700" aria-label="بستن">
                    <x-admin.icon name="x" class="size-4" />
                </button>
            </div>
        @endif
        @if (session('error') || $errors->any())
            <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 8000)" x-show="show" x-transition
                 role="alert"
                 class="pointer-events-auto flex items-start gap-3 rounded-lg border border-red-200 bg-white p-4 text-sm shadow-lg">
                <x-admin.icon name="exclamation" class="text-red-600" />
                <p class="flex-1">{{ session('error') ?? 'لطفاً خطاهای فرم را بررسی کنید.' }}</p>
                <button type="button" @click="show = false" class="text-neutral-400 hover:text-neutral-700" aria-label="بستن">
                    <x-admin.icon name="x" class="size-4" />
                </button>
            </div>
        @endif
    </div>

    <x-admin.media-library-modal />

    {{-- Confirmation dialog for forms marked with data-confirm --}}
    <div x-data x-cloak x-show="$store.confirmDialog.open" x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
         @keydown.escape.window="$store.confirmDialog.close()">
        <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl" role="alertdialog" aria-modal="true" aria-labelledby="confirm-dialog-message"
             @click.outside="$store.confirmDialog.close()">
            <div class="mb-4 flex size-10 items-center justify-center rounded-full bg-red-50 text-red-600">
                <x-admin.icon name="exclamation" />
            </div>
            <p id="confirm-dialog-message" class="mb-6 text-sm leading-7" x-text="$store.confirmDialog.message"></p>
            <div class="flex justify-end gap-2">
                <button type="button" class="rounded-lg border border-neutral-300 px-4 py-2 text-sm hover:bg-neutral-50" @click="$store.confirmDialog.close()">انصراف</button>
                <button type="button" class="rounded-lg bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700" x-ref="confirmButton"
                        x-effect="$store.confirmDialog.open && $nextTick(() => $refs.confirmButton.focus())"
                        @click="$store.confirmDialog.accept()">تأیید</button>
            </div>
        </div>
    </div>
@else
    <main class="flex min-h-screen items-center justify-center px-4 py-8">
        <div class="w-full max-w-md">
            @if (session('status'))
                <div class="mb-6 rounded border border-green-200 bg-green-50 p-3 text-sm text-green-700">{{ session('status') }}</div>
            @endif
            @yield('content')
        </div>
    </main>
@endauth
</body>
</html>
