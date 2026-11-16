{{-- The shared media library picker, opened by Alpine.store('mediaLibrary').open({...}). --}}
<div x-data x-init="$store.mediaLibrary.configure(@js(route('admin.media.index')), @js(csrf_token()))"
     x-cloak x-show="$store.mediaLibrary.isOpen" x-transition.opacity
     class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 sm:items-center sm:p-6"
     @keydown.escape.window="$store.mediaLibrary.isOpen && $store.mediaLibrary.close()">
    <div class="flex h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:h-[85vh] sm:rounded-2xl"
         role="dialog" aria-modal="true" aria-labelledby="media-library-title"
         @click.outside="$store.mediaLibrary.close()"
         @dragover.prevent="$store.mediaLibrary.isDragging = true"
         @dragleave.self="$store.mediaLibrary.isDragging = false"
         @drop.prevent="$store.mediaLibrary.isDragging = false; $store.mediaLibrary.upload($event.dataTransfer.files)">

        <div class="flex items-center justify-between gap-3 border-b border-neutral-200 px-5 py-4">
            <div>
                <h2 id="media-library-title" class="font-semibold text-neutral-900">کتابخانه مدیا</h2>
                <p class="text-xs text-neutral-500" x-text="$store.mediaLibrary.multiple ? 'روی هر تعداد تصویر که می‌خواهید کلیک کنید، سپس «افزودن» را بزنید.' : 'روی یک تصویر کلیک کنید تا انتخاب شود.'"></p>
            </div>
            <button type="button" @click="$store.mediaLibrary.close()" class="rounded-lg p-1.5 text-neutral-500 hover:bg-neutral-100" aria-label="بستن">
                <x-admin.icon name="x" />
            </button>
        </div>

        <div class="flex flex-wrap items-center gap-3 border-b border-neutral-100 bg-neutral-50 px-5 py-3">
            <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-brand-accent-dark px-4 py-2 text-sm font-medium text-white hover:bg-brand-accent-darker">
                <x-admin.icon name="download" class="size-4 rotate-180" />
                آپلود تصویر جدید
                <input type="file" class="sr-only" accept="image/jpeg,image/png,image/webp" :multiple="$store.mediaLibrary.multiple"
                       @change="$store.mediaLibrary.upload($event.target.files); $event.target.value = ''">
            </label>
            <span class="hidden text-xs text-neutral-500 sm:inline">یا فایل‌ها را به داخل این پنجره بکشید و رها کنید</span>
            <form class="relative mr-auto w-full sm:w-64" @submit.prevent="$store.mediaLibrary.fetch(1)">
                <x-admin.icon name="search" class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-neutral-400" />
                <input type="search" x-model="$store.mediaLibrary.query" placeholder="جستجو در نام یا متن جایگزین…"
                       @input.debounce.400ms="$store.mediaLibrary.fetch(1)"
                       class="admin-input pr-9">
            </form>
        </div>

        <ul x-show="$store.mediaLibrary.uploads.length" class="max-h-32 space-y-1 overflow-y-auto border-b border-neutral-100 px-5 py-2 text-xs">
            <template x-for="(upload, index) in $store.mediaLibrary.uploads" :key="index">
                <li class="flex items-center gap-3">
                    <span class="w-40 truncate" dir="ltr" x-text="upload.name"></span>
                    <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-neutral-100">
                        <div class="h-full rounded-full transition-all" :style="`width: ${upload.progress}%`"
                             :class="upload.status === 'failed' ? 'bg-red-500' : 'bg-green-500'"></div>
                    </div>
                    <span class="w-48 truncate" :class="upload.status === 'failed' ? 'text-red-600' : 'text-neutral-500'"
                          x-text="upload.status === 'failed' ? upload.error : (upload.status === 'done' ? 'آپلود شد ✓' : upload.progress + '٪')"></span>
                </li>
            </template>
        </ul>

        <div class="relative flex-1 overflow-y-auto p-5">
            <div x-show="$store.mediaLibrary.isDragging"
                 class="pointer-events-none absolute inset-3 z-10 flex items-center justify-center rounded-xl border-2 border-dashed border-brand-accent bg-orange-50/90 text-sm font-medium text-brand-accent-dark">
                فایل‌ها را رها کنید تا آپلود شوند
            </div>

            <p x-show="$store.mediaLibrary.loading && $store.mediaLibrary.items.length === 0" class="py-16 text-center text-sm text-neutral-500">در حال بارگذاری…</p>
            <div x-show="! $store.mediaLibrary.loading && $store.mediaLibrary.items.length === 0" class="py-16 text-center text-sm text-neutral-500">
                <x-admin.icon name="photo" class="mx-auto mb-2 size-10 text-neutral-300" />
                <span x-text="$store.mediaLibrary.query ? 'تصویری با این جستجو پیدا نشد.' : 'کتابخانه خالی است — اولین تصویر را آپلود کنید.'"></span>
            </div>

            <div class="grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-6" :class="$store.mediaLibrary.loading && 'opacity-60'">
                <template x-for="item in $store.mediaLibrary.items" :key="item.id">
                    <button type="button"
                            @click="$store.mediaLibrary.multiple ? $store.mediaLibrary.toggle(item) : $store.mediaLibrary.toggle(item)"
                            @dblclick="! $store.mediaLibrary.multiple && $store.mediaLibrary.pick(item)"
                            class="group relative overflow-hidden rounded-lg border-2 bg-neutral-100 text-right focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-accent"
                            :class="$store.mediaLibrary.isSelected(item) ? 'border-brand-accent-dark ring-2 ring-brand-accent/30' : 'border-transparent hover:border-neutral-300'"
                            :aria-pressed="$store.mediaLibrary.isSelected(item)" :title="item.alt || item.name">
                        <img :src="item.thumb" :alt="item.alt" loading="lazy" class="aspect-square w-full object-cover">
                        <span x-show="$store.mediaLibrary.isSelected(item)"
                              class="absolute left-1.5 top-1.5 flex size-6 items-center justify-center rounded-full bg-brand-accent-dark text-white shadow">
                            <x-admin.icon name="check" class="size-4" />
                        </span>
                        <span x-show="$store.mediaLibrary.isAlreadyUsed(item) && ! $store.mediaLibrary.isSelected(item)"
                              class="absolute right-1.5 top-1.5 rounded bg-white/90 px-1.5 py-0.5 text-[10px] text-neutral-700">انتخاب‌شده</span>
                        <span class="absolute inset-x-0 bottom-0 truncate bg-black/55 px-1.5 py-0.5 text-[10px] text-white"
                              x-text="(item.width && item.height ? item.width + '×' + item.height + ' · ' : '') + item.size_kb + 'KB'" dir="ltr"></span>
                    </button>
                </template>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-neutral-200 px-5 py-3">
            <div class="flex items-center gap-2 text-sm">
                <button type="button" class="admin-chip-button" :disabled="$store.mediaLibrary.page <= 1"
                        :class="$store.mediaLibrary.page <= 1 && 'opacity-40'"
                        @click="$store.mediaLibrary.fetch($store.mediaLibrary.page - 1)">→ قبلی</button>
                <span class="text-xs text-neutral-600">
                    صفحه <span x-text="$store.mediaLibrary.page.toLocaleString('fa-IR')"></span>
                    از <span x-text="$store.mediaLibrary.lastPage.toLocaleString('fa-IR')"></span>
                    (<span x-text="$store.mediaLibrary.total.toLocaleString('fa-IR')"></span> تصویر)
                </span>
                <button type="button" class="admin-chip-button" :disabled="$store.mediaLibrary.page >= $store.mediaLibrary.lastPage"
                        :class="$store.mediaLibrary.page >= $store.mediaLibrary.lastPage && 'opacity-40'"
                        @click="$store.mediaLibrary.fetch($store.mediaLibrary.page + 1)">بعدی ←</button>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs text-neutral-500" x-show="$store.mediaLibrary.selected.length"
                      x-text="$store.mediaLibrary.selected.length.toLocaleString('fa-IR') + ' تصویر انتخاب شده'"></span>
                <button type="button" @click="$store.mediaLibrary.close()" class="rounded-lg border border-neutral-300 px-4 py-2 text-sm hover:bg-neutral-50">انصراف</button>
                <button type="button" @click="$store.mediaLibrary.confirm()" :disabled="$store.mediaLibrary.selected.length === 0"
                        class="rounded-lg bg-brand-accent-dark px-5 py-2 text-sm font-medium text-white hover:bg-brand-accent-darker disabled:cursor-not-allowed disabled:opacity-40"
                        x-text="$store.mediaLibrary.multiple ? 'افزودن' : 'انتخاب تصویر'"></button>
            </div>
        </div>
    </div>
</div>
