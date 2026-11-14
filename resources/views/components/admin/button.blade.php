@props(['href' => null, 'variant' => 'primary', 'icon' => null])

@php
    $classes = 'inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-accent/40 '.match ($variant) {
        'secondary' => 'border border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-50',
        'danger' => 'bg-red-600 text-white hover:bg-red-700',
        default => 'bg-brand-accent-dark text-white hover:bg-brand-accent-darker',
    };
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<x-admin.icon :name="$icon" class="size-4" />@endif
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['type' => 'submit', 'class' => $classes]) }}>
        @if ($icon)<x-admin.icon :name="$icon" class="size-4" />@endif
        {{ $slot }}
    </button>
@endif
