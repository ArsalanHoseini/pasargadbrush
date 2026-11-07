{{-- Phone-only bottom bar (owner request 2026-11-07: home/search moved from the top to the bottom of the page). --}}
<div x-data="{ panel: null }" @keydown.escape.window="panel = null" class="md:hidden">
    <div x-cloak x-show="panel" x-transition.opacity class="fixed inset-0 z-40 bg-black/40" @click="panel = null"></div>

    <div x-cloak x-show="panel === 'products'" x-transition
         class="fixed inset-x-0 bottom-16 z-50 max-h-[70vh] overflow-y-auto rounded-t-2xl bg-white p-4 shadow-2xl">
        <p class="mb-3 text-sm font-semibold text-neutral-900">دسته‌بندی محصولات</p>
        <ul class="divide-y divide-neutral-100">
            @foreach ($navCategories as $category)
                <li><a href="{{ route('categories.show', $category['slug']) }}" class="block py-3 text-sm text-neutral-700">{{ $category['name'] }}</a></li>
            @endforeach
            <li><a href="{{ route('blog.index') }}" class="block py-3 text-sm text-neutral-700">مقالات</a></li>
            <li><a href="{{ route('pages.show', 'about-us') }}" class="block py-3 text-sm text-neutral-700">درباره ما</a></li>
            <li><a href="{{ route('pages.show', 'contact-us') }}" class="block py-3 text-sm text-neutral-700">تماس با ما</a></li>
        </ul>
    </div>

    <form x-cloak x-show="panel === 'search'" x-transition method="GET" action="{{ route('search.index') }}" role="search"
          class="fixed inset-x-0 bottom-16 z-50 flex gap-2 rounded-t-2xl bg-white p-4 shadow-2xl">
        <input type="search" name="q" placeholder="جستجوی محصول…" aria-label="جستجوی محصول" x-ref="search"
               class="w-full rounded-full border border-neutral-300 px-4 py-2 text-sm">
        <button type="submit" class="shrink-0 rounded-full bg-brand-accent-dark px-4 py-2 text-sm font-medium text-white">جستجو</button>
    </form>

    <nav class="fixed inset-x-0 bottom-0 z-50 border-t border-neutral-200 bg-white pb-[env(safe-area-inset-bottom)]" aria-label="ناوبری موبایل">
        <ul class="grid h-16 grid-cols-5 text-[11px] text-neutral-700">
            <li>
                <a href="{{ route('home') }}" class="flex h-full flex-col items-center justify-center gap-1 {{ request()->routeIs('home') ? 'text-brand-accent-dark' : '' }}">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M3 10.5L12 3l9 7.5V20a1 1 0 01-1 1h-5v-6h-6v6H4a1 1 0 01-1-1z" stroke-linejoin="round"/></svg>
                    خانه
                </a>
            </li>
            <li>
                <button type="button" @click="panel = panel === 'products' ? null : 'products'" :aria-expanded="panel === 'products'"
                        class="flex h-full w-full flex-col items-center justify-center gap-1">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                    محصولات
                </button>
            </li>
            <li>
                <button type="button" @click="panel = panel === 'search' ? null : 'search'; $nextTick(() => $refs.search.focus())" :aria-expanded="panel === 'search'"
                        class="flex h-full w-full flex-col items-center justify-center gap-1">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5" stroke-linecap="round"/></svg>
                    جستجو
                </button>
            </li>
            <li>
                <a href="{{ route('cart.show') }}" class="relative flex h-full flex-col items-center justify-center gap-1">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M3 3h2l.4 2M7 13h10l3-8H5.4M7 13L5.4 5M7 13l-1.5 7h11.5M9 20a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    سبد خرید
                    @if ($cartItemCount > 0)
                        <span class="absolute top-2 left-1/2 ml-2 flex h-4 min-w-4 items-center justify-center rounded-full bg-brand-accent-dark px-1 text-[10px] text-white">{{ $cartItemCount }}</span>
                    @endif
                </a>
            </li>
            <li>
                <a href="{{ auth('customer')->check() ? route('account.dashboard') : route('account.login') }}" class="flex h-full flex-col items-center justify-center gap-1">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M15.75 7.5a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.1a7.5 7.5 0 0115 0" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    {{ auth('customer')->check() ? 'حساب من' : 'ورود' }}
                </a>
            </li>
        </ul>
    </nav>
</div>
