@extends('admin.layout')

@section('title', 'کتابخانه مدیا')

@section('content')
<x-admin.page-header title="کتابخانه مدیا" :description="\App\Support\PersianDigits::toPersian(number_format($media->total())).' تصویر'" />

{{-- Upload: drag-and-drop or choose several files; each shows its own progress. --}}
<div x-data="mediaUploader(@js(route('admin.media.store')), @js(csrf_token()))" class="mb-6">
    <label class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed px-6 py-10 text-center transition"
           :class="isDragging ? 'border-brand-accent bg-orange-50' : 'border-neutral-300 bg-white hover:border-brand-accent hover:bg-orange-50/40'"
           @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false"
           @drop.prevent="isDragging = false; upload($event.dataTransfer.files)">
        <span class="flex size-12 items-center justify-center rounded-full bg-orange-100 text-brand-accent-dark">
            <x-admin.icon name="download" class="size-6 rotate-180" />
        </span>
        <span class="text-sm font-medium text-neutral-900">تصاویر را اینجا بکشید و رها کنید یا <span class="text-brand-accent-dark underline">کلیک کنید</span></span>
        <span class="text-xs text-neutral-500">JPG، PNG یا WEBP — هر فایل حداکثر ۵ مگابایت — چند فایل با هم مجاز است</span>
        <input type="file" multiple accept="image/jpeg,image/png,image/webp" class="sr-only"
               @change="upload($event.target.files); $event.target.value = ''">
    </label>

    <ul x-show="uploads.length" x-cloak class="mt-3 divide-y divide-neutral-100 rounded-xl border border-neutral-200 bg-white text-sm">
        <template x-for="(upload, index) in uploads" :key="index">
            <li class="flex items-center gap-3 px-4 py-2.5">
                <span class="w-48 truncate text-xs" dir="ltr" x-text="upload.name"></span>
                <div class="h-2 flex-1 overflow-hidden rounded-full bg-neutral-100">
                    <div class="h-full rounded-full transition-all" :style="`width: ${upload.progress}%`"
                         :class="upload.status === 'failed' ? 'bg-red-500' : 'bg-green-500'"></div>
                </div>
                <span class="w-56 truncate text-xs" :class="upload.status === 'failed' ? 'text-red-600' : 'text-neutral-500'"
                      x-text="{ waiting: 'در صف…', uploading: upload.progress + '٪', done: 'آپلود شد ✓', failed: upload.error }[upload.status]"></span>
            </li>
        </template>
    </ul>
    <p x-show="uploads.length && ! isBusy && uploads.some((upload) => upload.status === 'failed')" x-cloak class="mt-2 text-xs text-neutral-600">
        برخی فایل‌ها آپلود نشدند. <button type="button" class="font-medium text-brand-accent-dark underline" @click="window.location.reload()">به‌روزرسانی فهرست</button>
    </p>
</div>

<x-admin.filter-bar :action="route('admin.media.index')" placeholder="جستجو در نام فایل یا متن جایگزین…">
    <x-admin.filter-select name="usage" label="استفاده" :options="['unused' => 'استفاده‌نشده']" />
</x-admin.filter-bar>

@if ($media->isEmpty())
    <x-admin.empty-state icon="photo">
        {{ request()->hasAny(['q', 'usage']) ? 'تصویری با این فیلترها پیدا نشد.' : 'هنوز هیچ تصویری آپلود نشده است — از کادر بالا شروع کنید.' }}
    </x-admin.empty-state>
@else
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6">
        @foreach ($media as $item)
            <div class="flex flex-col overflow-hidden rounded-xl border border-neutral-200 bg-white"
                 x-data="{ editing: false, alt: @js((string) $item->alt_text), copied: false }">
                <a href="{{ $item->url() }}" target="_blank" rel="noopener" class="block bg-neutral-100" title="نمایش اندازه اصلی">
                    <img src="{{ $item->thumbnailUrl(480) }}" alt="{{ $item->alt_text }}" loading="lazy" class="aspect-square w-full object-cover">
                </a>
                <div class="flex flex-1 flex-col gap-1.5 p-3 text-xs">
                    <p class="truncate text-neutral-500" dir="ltr" title="{{ basename($item->path) }}">{{ basename($item->path) }}</p>
                    <p class="text-neutral-400" dir="ltr">
                        @if ($item->width && $item->height){{ $item->width }}×{{ $item->height }} · @endif{{ number_format($item->size / 1024) }} KB
                    </p>
                    <p>
                        @if ($item->usage_count > 0)
                            <span class="rounded bg-green-50 px-1.5 py-0.5 text-green-700">استفاده در {{ \App\Support\PersianDigits::toPersian((string) $item->usage_count) }} مورد</span>
                        @else
                            <span class="rounded bg-neutral-100 px-1.5 py-0.5 text-neutral-500">استفاده‌نشده</span>
                        @endif
                    </p>

                    <p x-show="! editing" class="cursor-pointer truncate text-neutral-700 hover:text-brand-accent-dark" @click="editing = true; $nextTick(() => $refs.alt.focus())"
                       :title="alt || 'افزودن متن جایگزین'">
                        <span x-text="alt || '+ متن جایگزین (alt)'" :class="! alt && 'text-amber-700'"></span>
                    </p>
                    <form x-show="editing" x-cloak method="POST" action="{{ route('admin.media.update', $item) }}" class="flex gap-1">
                        @csrf
                        @method('PATCH')
                        <input x-ref="alt" name="alt_text" x-model="alt" maxlength="255" class="admin-input admin-input-sm text-xs" placeholder="توضیح تصویر">
                        <button type="submit" class="rounded-lg bg-neutral-900 px-2 text-white">ذخیره</button>
                    </form>

                    <div class="mt-auto flex items-center justify-between gap-2 border-t border-neutral-100 pt-2">
                        <button type="button" class="text-neutral-600 hover:text-neutral-900"
                                @click="navigator.clipboard.writeText(@js($item->url())); copied = true; setTimeout(() => copied = false, 1500)"
                                x-text="copied ? 'کپی شد ✓' : 'کپی آدرس'">کپی آدرس</button>
                        <form method="POST" action="{{ route('admin.media.destroy', $item) }}"
                              data-confirm="{{ $item->usage_count > 0 ? 'این تصویر در '.$item->usage_count.' محصول/مقاله استفاده شده و با حذف، از آن‌ها هم برداشته می‌شود. حذف شود؟' : 'این تصویر حذف شود؟' }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800">حذف</button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <x-admin.pagination :paginator="$media" />
@endif
@endsection
