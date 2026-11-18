@extends('admin.layout')

@section('title', 'منوهای سایت')

@section('content')
<x-admin.page-header title="منوهای سایت" description="پیوندهای منوی بالای سایت و «دسترسی سریع» فوتر را اضافه، حذف، مرتب یا زیرمنو کنید.">
    <x-slot:actions>
        <x-admin.button variant="secondary" icon="external" :href="route('home')" target="_blank" rel="noopener">مشاهده سایت</x-admin.button>
    </x-slot:actions>
</x-admin.page-header>

<div class="mb-6 flex gap-1 border-b border-neutral-200" role="tablist">
    @foreach (\App\Models\MenuItem::LOCATIONS as $key => $label)
        <a href="{{ route('admin.menus.edit', $key) }}" role="tab" @if ($key === $location) aria-selected="true" @endif
           @class([
               '-mb-px border-b-2 px-4 py-2.5 text-sm font-medium transition',
               'border-brand-accent-dark text-neutral-900' => $key === $location,
               'border-transparent text-neutral-500 hover:text-neutral-800' => $key !== $location,
           ])>{{ $label }}</a>
    @endforeach
</div>

<x-admin.form-errors />

<form method="POST" action="{{ route('admin.menus.update', $location) }}"
      x-data="menuEditor(@js($items), @js($linkGroups))">
    @csrf
    @method('PUT')
    <input type="hidden" name="menu_json" :value="serialized()">

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- The menu tree --}}
        <div class="min-w-0 lg:col-span-2">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2 class="admin-card-title">{{ \App\Models\MenuItem::LOCATIONS[$location] }}</h2>
                    <span class="text-xs text-neutral-500">ترتیب از راست به چپ در سایت = از بالا به پایین در این فهرست</span>
                </div>

                <div class="p-5">
                    <template x-if="items.length === 0">
                        <x-admin.empty-state icon="menu">منو خالی است — از ستون کناری پیوند اضافه کنید.</x-admin.empty-state>
                    </template>

                    <ol class="space-y-3">
                        <template x-for="(item, index) in items" :key="item.key">
                            <li class="rounded-xl border border-neutral-200 bg-white">
                                {{-- Top-level item --}}
                                <div class="flex flex-wrap items-center gap-2 p-3">
                                    <div class="flex flex-col">
                                        <button type="button" @click="move(items, index, -1)" :disabled="index === 0" class="rounded px-1 text-neutral-500 hover:bg-neutral-100 disabled:opacity-25" title="بالا" aria-label="انتقال به بالا">▲</button>
                                        <button type="button" @click="move(items, index, 1)" :disabled="index === items.length - 1" class="rounded px-1 text-neutral-500 hover:bg-neutral-100 disabled:opacity-25" title="پایین" aria-label="انتقال به پایین">▼</button>
                                    </div>
                                    <button type="button" class="flex min-w-0 flex-1 items-center gap-2 text-right" @click="openKey = openKey === item.key ? null : item.key">
                                        <span class="truncate font-medium text-neutral-900" x-text="item.label || '(بدون عنوان)'"></span>
                                        <span x-show="item.type === 'categories'" class="shrink-0 rounded bg-orange-50 px-1.5 py-0.5 text-[11px] text-brand-accent-dark">کشویی دسته‌های محصول</span>
                                        <span x-show="item.type === 'link'" class="truncate text-xs text-neutral-400" dir="ltr" x-text="item.url"></span>
                                        <span x-show="item.children.length" class="shrink-0 rounded bg-neutral-100 px-1.5 py-0.5 text-[11px] text-neutral-600" x-text="item.children.length.toLocaleString('fa-IR') + ' زیرمنو'"></span>
                                    </button>
                                    <div class="flex items-center gap-1">
                                        <button type="button" x-show="canIndent(index)" @click="indent(index)" class="admin-chip-button" title="زیرمنوی آیتم بالایی شود">← زیرمنو کن</button>
                                        <button type="button" @click="openKey = openKey === item.key ? null : item.key" class="admin-chip-button" x-text="openKey === item.key ? 'بستن' : 'ویرایش'"></button>
                                        <button type="button" @click="remove(items, index)" class="rounded-lg p-1.5 text-red-600 hover:bg-red-50" title="حذف" aria-label="حذف آیتم">
                                            <x-admin.icon name="x" class="size-4" />
                                        </button>
                                    </div>
                                </div>

                                <div x-show="openKey === item.key" x-cloak class="grid gap-3 border-t border-neutral-100 bg-neutral-50/60 p-3 sm:grid-cols-2">
                                    <div>
                                        <label class="admin-label">عنوان</label>
                                        <input x-model="item.label" maxlength="100" class="admin-input" required>
                                    </div>
                                    <div x-show="item.type === 'link'">
                                        <label class="admin-label">آدرس</label>
                                        <input x-model="item.url" dir="ltr" class="admin-input" placeholder="/page/about-us یا https://…" :required="item.type === 'link'">
                                    </div>
                                    <p x-show="item.type === 'categories'" class="text-xs leading-6 text-neutral-500">
                                        این آیتم در سایت به‌صورت منوی کشویی، فهرست دسته‌های اصلی محصولات را نشان می‌دهد (خودکار از دسته‌بندی‌ها خوانده می‌شود).
                                    </p>
                                    <label x-show="item.type === 'link'" class="flex items-center gap-2 text-sm sm:col-span-2">
                                        <input type="checkbox" x-model="item.open_in_new_tab" class="admin-check">
                                        باز شدن در زبانه‌ی جدید
                                    </label>
                                </div>

                                {{-- Children --}}
                                <ol x-show="item.children.length" class="space-y-2 border-t border-neutral-100 p-3 pr-10">
                                    <template x-for="(child, childIndex) in item.children" :key="child.key">
                                        <li class="rounded-lg border border-neutral-200 bg-neutral-50">
                                            <div class="flex flex-wrap items-center gap-2 p-2">
                                                <span class="text-neutral-300">↳</span>
                                                <div class="flex flex-col text-xs">
                                                    <button type="button" @click="move(item.children, childIndex, -1)" :disabled="childIndex === 0" class="px-1 text-neutral-500 disabled:opacity-25" aria-label="بالا">▲</button>
                                                    <button type="button" @click="move(item.children, childIndex, 1)" :disabled="childIndex === item.children.length - 1" class="px-1 text-neutral-500 disabled:opacity-25" aria-label="پایین">▼</button>
                                                </div>
                                                <button type="button" class="flex min-w-0 flex-1 items-center gap-2 text-right text-sm" @click="openKey = openKey === child.key ? null : child.key">
                                                    <span class="truncate" x-text="child.label || '(بدون عنوان)'"></span>
                                                    <span class="truncate text-xs text-neutral-400" dir="ltr" x-text="child.url"></span>
                                                </button>
                                                <button type="button" @click="outdent(item, childIndex)" class="admin-chip-button" title="به سطح اول منو برگردد">→ سطح اول</button>
                                                <button type="button" @click="openKey = openKey === child.key ? null : child.key" class="admin-chip-button" x-text="openKey === child.key ? 'بستن' : 'ویرایش'"></button>
                                                <button type="button" @click="remove(item.children, childIndex)" class="rounded-lg p-1.5 text-red-600 hover:bg-red-50" aria-label="حذف زیرمنو">
                                                    <x-admin.icon name="x" class="size-4" />
                                                </button>
                                            </div>
                                            <div x-show="openKey === child.key" x-cloak class="grid gap-3 border-t border-neutral-200 p-3 sm:grid-cols-2">
                                                <div>
                                                    <label class="admin-label">عنوان</label>
                                                    <input x-model="child.label" maxlength="100" class="admin-input" required>
                                                </div>
                                                <div>
                                                    <label class="admin-label">آدرس</label>
                                                    <input x-model="child.url" dir="ltr" class="admin-input" required>
                                                </div>
                                                <label class="flex items-center gap-2 text-sm sm:col-span-2">
                                                    <input type="checkbox" x-model="child.open_in_new_tab" class="admin-check">
                                                    باز شدن در زبانه‌ی جدید
                                                </label>
                                            </div>
                                        </li>
                                    </template>
                                </ol>
                                <div x-show="item.type === 'link'" class="border-t border-dashed border-neutral-200 px-3 py-2 pr-10">
                                    <button type="button" @click="add('link', item)" class="text-xs font-medium text-brand-accent-dark hover:underline">+ افزودن زیرمنو</button>
                                </div>
                            </li>
                        </template>
                    </ol>
                </div>
            </div>
        </div>

        {{-- Add links --}}
        <div class="space-y-6">
            <div class="admin-card">
                <div class="admin-card-header"><h2 class="admin-card-title">افزودن به منو</h2></div>
                <div class="admin-card-body">
                    <button type="button" @click="add('link')" class="admin-chip-button w-full justify-center py-2">+ پیوند دلخواه (آدرس دستی)</button>
                    @if ($location === 'header')
                        <button type="button" x-show="! hasCategories" @click="add('categories')" class="admin-chip-button w-full justify-center py-2">+ منوی کشویی «محصولات»</button>
                    @endif

                    <template x-for="group in linkGroups" :key="group.label">
                        <div x-show="group.links.length" x-data="{ expanded: false }" class="rounded-lg border border-neutral-200">
                            <button type="button" @click="expanded = ! expanded" class="flex w-full items-center justify-between px-3 py-2 text-sm font-medium">
                                <span x-text="group.label"></span>
                                <span class="text-neutral-400" x-text="expanded ? '−' : '+'"></span>
                            </button>
                            <ul x-show="expanded" x-cloak class="max-h-60 divide-y divide-neutral-100 overflow-y-auto border-t border-neutral-100">
                                <template x-for="link in group.links" :key="link.url">
                                    <li class="flex items-center justify-between gap-2 px-3 py-1.5 text-sm">
                                        <span class="truncate" x-text="link.label"></span>
                                        <button type="button" @click="addFromOption(link)" class="shrink-0 text-xs font-medium text-brand-accent-dark hover:underline">افزودن</button>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </template>
                </div>
            </div>

            <div class="rounded-xl border border-blue-100 bg-blue-50/60 p-4 text-xs leading-6 text-blue-900">
                <p class="mb-1 font-medium">راهنما</p>
                <ul class="list-disc space-y-1 pr-4">
                    <li>با ▲ ▼ ترتیب را عوض کنید.</li>
                    <li>«زیرمنو کن» آیتم را زیر آیتم بالایی می‌برد؛ در سایت به‌صورت منوی کشویی نمایش داده می‌شود.</li>
                    <li>آدرس صفحات همین سایت را با «/» شروع کنید (مثلاً ‎/blog‎).</li>
                    <li>تغییرات تا «ذخیره منو» را نزنید اعمال نمی‌شوند.</li>
                </ul>
            </div>
        </div>
    </div>

    <x-admin.save-bar :cancel="route('admin.menus.edit', $location)" label="ذخیره منو" />
</form>
@endsection
