@props(['cancel', 'view' => null, 'label' => 'ذخیره تغییرات'])

{{-- Sticky action bar at the bottom of long edit forms, so «save» is always in reach. --}}
<div class="sticky bottom-0 z-20 -mx-4 mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-neutral-200 bg-white/95 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6">
    <div class="flex items-center gap-3 text-sm">
        @if ($view)
            <a href="{{ $view }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-neutral-600 hover:text-neutral-900">
                <x-admin.icon name="external" class="size-4" />
                مشاهده در سایت
            </a>
        @endif
        {{ $slot }}
    </div>
    <div class="flex items-center gap-2">
        <x-admin.button variant="secondary" :href="$cancel">انصراف</x-admin.button>
        <x-admin.button icon="check">{{ $label }}</x-admin.button>
    </div>
</div>
