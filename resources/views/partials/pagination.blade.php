{{-- Persian, RTL page navigation (previous/next plus numbered pages). Laravel's default
     pagination view, so every ->links() on the site and in the account uses it too. --}}
@if ($paginator->hasPages())
    @php
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();
        $pages = collect([1, 2, $current - 2, $current - 1, $current, $current + 1, $current + 2, $last - 1, $last])
            ->filter(fn (int $page) => $page >= 1 && $page <= $last)
            ->unique()
            ->sort()
            ->values();
        $fa = fn (int $number) => \App\Support\PersianDigits::toPersian(number_format($number));
    @endphp
    <nav class="flex flex-wrap items-center justify-between gap-3" aria-label="صفحه‌بندی">
        <p class="text-xs text-neutral-500">
            نمایش {{ $fa($paginator->firstItem()) }} تا {{ $fa($paginator->lastItem()) }} از {{ $fa($paginator->total()) }} مورد
        </p>
        <ul class="flex flex-wrap items-center gap-1 text-sm">
            <li>
                @if ($paginator->onFirstPage())
                    <span class="flex h-9 items-center rounded-lg border border-neutral-200 px-3 text-neutral-300">→ قبلی</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="flex h-9 items-center rounded-lg border border-neutral-300 bg-white px-3 text-neutral-700 hover:bg-neutral-50">→ قبلی</a>
                @endif
            </li>
            @foreach ($pages as $index => $page)
                @if ($index > 0 && $page - $pages[$index - 1] > 1)
                    <li class="px-1 text-neutral-400">…</li>
                @endif
                <li>
                    @if ($page === $current)
                        <span aria-current="page" class="flex size-9 items-center justify-center rounded-lg bg-brand-accent-dark font-medium text-white">{{ $fa($page) }}</span>
                    @else
                        <a href="{{ $paginator->url($page) }}" class="flex size-9 items-center justify-center rounded-lg border border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-50">{{ $fa($page) }}</a>
                    @endif
                </li>
            @endforeach
            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="flex h-9 items-center rounded-lg border border-neutral-300 bg-white px-3 text-neutral-700 hover:bg-neutral-50">بعدی ←</a>
                @else
                    <span class="flex h-9 items-center rounded-lg border border-neutral-200 px-3 text-neutral-300">بعدی ←</span>
                @endif
            </li>
        </ul>
    </nav>
@endif
