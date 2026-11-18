@props(['action', 'placeholder' => 'جستجو…', 'keep' => []])

@php
    /** Query keys this bar owns or keeps (e.g. a status tab); anything set means "filtered". */
    $isFiltered = collect(request()->except(['page', ...$keep]))->filter(fn ($value) => filled($value))->isNotEmpty();
@endphp

<form method="GET" action="{{ $action }}" role="search"
      class="mb-4 flex flex-wrap items-center gap-2 rounded-xl border border-neutral-200 bg-white p-3">
    @foreach ($keep as $keptKey)
        @if (filled(request($keptKey)))
            <input type="hidden" name="{{ $keptKey }}" value="{{ request($keptKey) }}">
        @endif
    @endforeach

    <div class="relative min-w-48 flex-1">
        <x-admin.icon name="search" class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-neutral-400" />
        <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ $placeholder }}" aria-label="{{ $placeholder }}"
               class="w-full rounded-lg border border-neutral-200 py-2 pl-3 pr-9 text-sm focus:border-brand-accent focus:outline-none focus:ring-2 focus:ring-brand-accent/20">
    </div>

    {{ $slot }}

    <x-admin.button variant="secondary" icon="funnel">اعمال</x-admin.button>

    @if ($isFiltered)
        <a href="{{ $action }}{{ collect($keep)->filter(fn ($key) => filled(request($key)))->isNotEmpty() ? '?'.http_build_query(request()->only($keep)) : '' }}"
           class="px-2 text-sm text-neutral-500 hover:text-neutral-900">پاک کردن فیلترها</a>
    @endif
</form>
