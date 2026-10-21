<header class="border-b border-neutral-200 bg-white">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-6 px-4 py-4 sm:px-6">
        <a href="{{ route('home') }}" class="shrink-0">
            <img src="{{ asset('images/logo.png') }}" alt="پاسارگاد برس" class="h-10 w-auto">
        </a>

        <nav class="hidden items-center gap-6 text-sm font-medium md:flex" aria-label="ناوبری اصلی">
            <a href="{{ route('home') }}" class="text-neutral-700 hover:text-neutral-950">صفحه اصلی</a>

            <div class="group relative">
                <button type="button" class="flex items-center gap-1 text-neutral-700 hover:text-neutral-950">
                    محصولات
                    <svg class="h-3 w-3" viewBox="0 0 12 12" fill="none" aria-hidden="true">
                        <path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <div class="invisible absolute right-0 top-full z-10 w-64 rounded-lg border border-neutral-200 bg-white p-2 opacity-0 shadow-lg transition group-hover:visible group-hover:opacity-100">
                    @foreach ($navCategories as $category)
                        <a href="{{ route('categories.show', $category['slug']) }}"
                           class="block rounded px-3 py-2 text-sm text-neutral-700 hover:bg-neutral-50 hover:text-neutral-950">
                            {{ $category['name'] }}
                        </a>
                    @endforeach
                </div>
            </div>

            <a href="{{ route('blog.index') }}" class="text-neutral-700 hover:text-neutral-950">مقالات</a>
            <a href="{{ route('pages.show', 'contact-us') }}" class="text-neutral-700 hover:text-neutral-950">تماس با ما</a>
        </nav>

        <form method="GET" action="{{ route('search.index') }}" class="hidden max-w-xs grow md:flex">
            <input type="search" name="q" placeholder="جستجوی محصول…" value="{{ request('q') }}"
                   class="w-full rounded-full border border-neutral-300 px-4 py-1.5 text-sm">
        </form>

        <a href="tel:{{ setting('contact_mobile') }}"
           class="hidden shrink-0 items-center gap-2 rounded-full bg-brand-charcoal px-4 py-2 text-sm font-medium text-white hover:bg-neutral-800 sm:flex"
           dir="ltr">
            {{ setting('contact_mobile') }}
        </a>
    </div>
</header>
