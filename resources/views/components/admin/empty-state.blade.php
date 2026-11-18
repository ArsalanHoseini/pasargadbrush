@props(['icon' => 'inbox'])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center gap-2 rounded-xl border border-dashed border-neutral-300 bg-white px-4 py-14 text-center text-sm text-neutral-500']) }}>
    <x-admin.icon :name="$icon" class="size-8 text-neutral-300" />
    {{ $slot }}
</div>
