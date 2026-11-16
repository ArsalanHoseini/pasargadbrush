@props([
    'name',
    'items' => [],
    'multiple' => false,
    'emptyText' => 'تصویری انتخاب نشده است.',
])

{{--
    A featured image / gallery field. $items: list of ['id', 'url', 'thumb', 'alt'].
    Opens the shared media library modal (admin layout) to pick or upload images.
--}}
<div x-data="mediaPicker({ multiple: @js((bool) $multiple), initial: @js(array_values($items)) })" {{ $attributes }}>
    <template x-for="item in items" :key="item.id">
        <input type="hidden" name="{{ $name }}" :value="item.id">
    </template>
    @unless ($multiple)
        <template x-if="items.length === 0"><input type="hidden" name="{{ $name }}" value=""></template>
    @endunless

    @if ($multiple)
        <div class="grid grid-cols-3 gap-3" x-show="items.length > 0">
            <template x-for="(item, index) in items" :key="item.id">
                <div class="group relative overflow-hidden rounded-lg border border-neutral-200 bg-neutral-50">
                    <img :src="item.thumb || item.url" :alt="item.alt" class="aspect-square w-full object-cover" loading="lazy">
                    <span x-show="index === 0" class="absolute right-1.5 top-1.5 rounded bg-brand-accent-dark px-1.5 py-0.5 text-[10px] font-medium text-white">تصویر اصلی</span>
                    <div class="absolute inset-x-0 bottom-0 flex items-center justify-between gap-1 bg-black/60 p-1 opacity-100 transition sm:opacity-0 sm:group-hover:opacity-100 sm:group-focus-within:opacity-100">
                        <div class="flex gap-0.5">
                            <button type="button" @click="move(index, -1)" :disabled="index === 0" class="rounded px-1.5 py-0.5 text-xs text-white hover:bg-white/20 disabled:opacity-30" title="جابه‌جایی به راست" aria-label="جابه‌جایی به قبل">→</button>
                            <button type="button" @click="move(index, 1)" :disabled="index === items.length - 1" class="rounded px-1.5 py-0.5 text-xs text-white hover:bg-white/20 disabled:opacity-30" title="جابه‌جایی به چپ" aria-label="جابه‌جایی به بعد">←</button>
                            <button type="button" x-show="index > 0" @click="makeFirst(index)" class="rounded px-1.5 py-0.5 text-[10px] text-white hover:bg-white/20" title="تصویر اصلی شود">اصلی</button>
                        </div>
                        <button type="button" @click="remove(index)" class="rounded p-0.5 text-white hover:bg-red-600" title="برداشتن از گالری" aria-label="برداشتن">
                            <x-admin.icon name="x" class="size-4" />
                        </button>
                    </div>
                </div>
            </template>
            <button type="button" @click="open()"
                    class="flex aspect-square flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed border-neutral-300 text-xs text-neutral-500 transition hover:border-brand-accent hover:text-brand-accent-dark">
                <x-admin.icon name="plus" />
                افزودن تصویر
            </button>
        </div>
        <button type="button" x-show="items.length === 0" @click="open()"
                class="flex w-full flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-neutral-300 px-4 py-8 text-sm text-neutral-500 transition hover:border-brand-accent hover:bg-orange-50/40 hover:text-brand-accent-dark">
            <x-admin.icon name="photo" class="size-8" />
            <span>{{ $emptyText }}</span>
            <span class="font-medium text-brand-accent-dark">انتخاب یا آپلود تصاویر</span>
        </button>
        <p class="admin-hint" x-show="items.length > 1">تصویر اول، تصویر اصلی محصول است. با فلش‌ها ترتیب را عوض کنید.</p>
    @else
        <template x-if="items.length > 0">
            <div class="relative overflow-hidden rounded-lg border border-neutral-200 bg-neutral-50">
                <img :src="items[0].thumb || items[0].url" :alt="items[0].alt" class="aspect-video w-full object-cover">
                <div class="flex items-center justify-between gap-2 border-t border-neutral-200 bg-white p-2">
                    <button type="button" @click="open()" class="admin-chip-button">تعویض تصویر</button>
                    <button type="button" @click="remove(0)" class="text-xs text-red-600 hover:text-red-800">حذف تصویر</button>
                </div>
            </div>
        </template>
        <button type="button" x-show="items.length === 0" @click="open()"
                class="flex w-full flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-neutral-300 px-4 py-8 text-sm text-neutral-500 transition hover:border-brand-accent hover:bg-orange-50/40 hover:text-brand-accent-dark">
            <x-admin.icon name="photo" class="size-8" />
            <span>{{ $emptyText }}</span>
            <span class="font-medium text-brand-accent-dark">انتخاب یا آپلود تصویر</span>
        </button>
    @endif
</div>
