<header class="border-b border-neutral-200 bg-white">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-6 px-4 py-3 sm:px-6">
        <a href="{{ route('home') }}" class="shrink-0">
            <img src="{{ asset('images/logo.webp') }}" alt="برس صنعت پاسارگاد" width="386" height="109" class="h-11 w-auto" fetchpriority="high">
        </a>

        <nav class="hidden items-center gap-6 text-sm font-medium md:flex" aria-label="ناوبری اصلی">
            <a href="{{ route('home') }}" class="whitespace-nowrap text-neutral-700 hover:text-neutral-950">صفحه اصلی</a>

            <div class="group relative">
                <button type="button" class="flex items-center gap-1 text-neutral-700 hover:text-neutral-950">
                    محصولات
                    <svg class="h-3 w-3" viewBox="0 0 12 12" fill="none" aria-hidden="true">
                        <path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <div class="invisible absolute right-0 top-full z-10 w-64 rounded-lg border border-neutral-200 bg-white p-2 opacity-0 shadow-lg transition group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100">
                    @foreach ($navCategories as $category)
                        <a href="{{ route('categories.show', $category['slug']) }}"
                           class="block rounded px-3 py-2 text-sm text-neutral-700 hover:bg-neutral-50 hover:text-neutral-950">
                            {{ $category['name'] }}
                        </a>
                    @endforeach
                </div>
            </div>

            <a href="{{ route('blog.index') }}" class="text-neutral-700 hover:text-neutral-950">مقالات</a>
            <a href="{{ route('pages.show', 'about-us') }}" class="whitespace-nowrap text-neutral-700 hover:text-neutral-950">درباره ما</a>
            <a href="{{ route('pages.show', 'contact-us') }}" class="whitespace-nowrap text-neutral-700 hover:text-neutral-950">تماس با ما</a>
        </nav>

        <form method="GET" action="{{ route('search.index') }}" class="hidden max-w-xs grow lg:flex" role="search">
            <input type="search" name="q" placeholder="جستجوی محصول…" value="{{ request('q') }}" aria-label="جستجوی محصول"
                   class="w-full rounded-full border border-neutral-300 px-4 py-1.5 text-sm">
        </form>

        <div class="hidden shrink-0 items-center gap-5 md:flex">
            <a href="{{ auth('customer')->check() ? route('account.dashboard') : route('account.login') }}"
               class="flex items-center gap-1 text-sm text-neutral-700 hover:text-neutral-950"
               aria-label="{{ auth('customer')->check() ? 'حساب کاربری' : 'ورود / ثبت‌نام' }}">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path d="M15.75 7.5a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.1a7.5 7.5 0 0115 0" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span class="hidden xl:inline">{{ auth('customer')->check() ? 'حساب من' : 'ورود' }}</span>
            </a>

            <a href="{{ route('cart.show') }}" class="relative text-neutral-700 hover:text-neutral-950" aria-label="سبد خرید">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path d="M3 3h2l.4 2M7 13h10l3-8H5.4M7 13L5.4 5M7 13l-1.5 7h11.5M9 20a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2z" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                @if ($cartItemCount > 0)
                    <span class="absolute -top-2 -right-2 flex h-4 w-4 items-center justify-center rounded-full bg-brand-accent-dark text-[10px] text-white">
                        {{ $cartItemCount }}
                    </span>
                @endif
            </a>
        </div>

        <a href="tel:{{ setting('contact_mobile') }}"
           class="flex shrink-0 items-center gap-2 rounded-full bg-brand-charcoal px-4 py-2 text-sm font-medium text-white hover:bg-neutral-800"
           dir="ltr" aria-label="تماس تلفنی">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6.6 10.8a15.1 15.1 0 006.6 6.6l2.2-2.2a1 1 0 011-.25 11.4 11.4 0 003.6.57 1 1 0 011 1V20a1 1 0 01-1 1A17 17 0 013 4a1 1 0 011-1h3.5a1 1 0 011 1c0 1.25.2 2.45.57 3.6a1 1 0 01-.25 1z"/></svg>
            <span class="hidden sm:inline">{{ setting('contact_mobile') }}</span>
        </a>
    </div>
</header>

@include('partials.mobile-nav')
