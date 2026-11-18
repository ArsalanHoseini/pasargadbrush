@props(['title', 'description' => null])

<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
    <div class="min-w-0">
        <h1 class="text-xl font-bold text-neutral-900">{{ $title }}</h1>
        @if ($description)
            <p class="mt-1 text-sm text-neutral-500">{{ $description }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
