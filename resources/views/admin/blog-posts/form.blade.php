@extends('admin.layout')

@section('title', $post->exists ? 'ویرایش مقاله' : 'مقاله جدید')

@section('content')
<h1 class="mb-6 text-lg font-semibold">{{ $post->exists ? 'ویرایش مقاله' : 'مقاله جدید' }}</h1>

@if ($errors->any())
    <div class="mb-6 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<form method="POST"
      action="{{ $post->exists ? route('admin.blog-posts.update', $post) : route('admin.blog-posts.store') }}"
      class="max-w-2xl space-y-4 rounded-lg border border-neutral-200 bg-white p-6">
    @csrf
    @if ($post->exists) @method('PUT') @endif

    <div>
        <label class="mb-1 block text-sm font-medium">عنوان</label>
        <input name="title" value="{{ old('title', $post->title) }}" required
               class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium">نامک (slug) — خالی بگذارید تا خودکار ساخته شود</label>
        <input name="slug" value="{{ old('slug', $post->slug) }}" dir="ltr"
               class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
    </div>

    <div class="flex gap-4">
        <div class="flex-1">
            <label class="mb-1 block text-sm font-medium">دسته</label>
            <select name="blog_category_id" class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
                <option value="">— بدون دسته —</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((int) old('blog_category_id', $post->blog_category_id) === $category->id)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="flex-1">
            <label class="mb-1 block text-sm font-medium">وضعیت</label>
            <select name="status" class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
                <option value="draft" @selected(old('status', $post->status ?? 'draft') === 'draft')>پیش‌نویس</option>
                <option value="published" @selected(old('status', $post->status) === 'published')>منتشرشده</option>
            </select>
        </div>
    </div>

    <div class="flex gap-4">
        {{-- The browser's date picker is Gregorian (it can't be changed); show the Persian date live beside it. --}}
        <div class="flex-1" x-data="{
                 value: @js(old('published_at', $post->published_at?->format('Y-m-d\TH:i'))),
                 get persian() {
                     if (! this.value) return 'هم‌اکنون';
                     // Same wall-clock time as typed (Tehran, like the app), only converted to the Persian calendar.
                     const date = new Date(this.value + ':00Z');
                     return isNaN(date) ? '' : new Intl.DateTimeFormat('fa-IR-u-ca-persian', { dateStyle: 'long', timeStyle: 'short', timeZone: 'UTC' }).format(date);
                 },
             }">
            <label for="published_at" class="mb-1 block text-sm font-medium">تاریخ انتشار (خالی = هم‌اکنون؛ تاریخ آینده = زمان‌بندی‌شده)</label>
            <input id="published_at" type="datetime-local" name="published_at" dir="ltr" x-model="value"
                   value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}"
                   class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
            <p class="mt-1 text-xs text-neutral-600">به تاریخ شمسی: <span class="font-medium" x-text="persian">{{ $post->published_at ? persian_date($post->published_at) : 'هم‌اکنون' }}</span></p>
        </div>
        <div class="flex items-center gap-2 pt-6">
            <input type="checkbox" name="featured" id="featured" value="1" @checked(old('featured', $post->featured))>
            <label for="featured" class="text-sm font-medium">مقاله ویژه</label>
        </div>
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium">خلاصه</label>
        <textarea name="excerpt" rows="2" class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">{{ old('excerpt', $post->excerpt) }}</textarea>
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium">برچسب‌ها (با کاما یا «،» جدا کنید)</label>
        <input name="tags" value="{{ old('tags', $currentTags) }}"
               class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium">متن مقاله</label>
        <x-rich-text-editor name="body" :value="old('body', $post->body)" />
    </div>

    <div>
        <label class="mb-2 block text-sm font-medium">تصویر شاخص</label>
        @if ($media->isEmpty())
            <p class="text-sm text-neutral-500">
                هنوز هیچ فایلی در <a href="{{ route('admin.media.index') }}" class="underline">کتابخانه مدیا</a> آپلود نشده است.
            </p>
        @else
            <div class="grid grid-cols-4 gap-3 sm:grid-cols-6">
                <label class="flex cursor-pointer flex-col items-center gap-1 text-xs">
                    <input type="radio" name="featured_media_id" value="" @checked(! old('featured_media_id', $currentFeaturedId))>
                    بدون تصویر
                </label>
                @foreach ($media as $item)
                    <label class="cursor-pointer">
                        <input type="radio" name="featured_media_id" value="{{ $item->id }}"
                               class="peer sr-only"
                               @checked((int) old('featured_media_id', $currentFeaturedId) === $item->id)>
                        <img src="{{ $item->url() }}" alt="{{ $item->alt_text }}"
                             class="aspect-square w-full rounded border-2 border-transparent object-cover peer-checked:border-neutral-900">
                    </label>
                @endforeach
            </div>
        @endif
    </div>

    <div class="border-t border-neutral-100 pt-4">
        <p class="mb-2 text-sm font-medium text-neutral-500">SEO (اختیاری)</p>
        <div class="space-y-3">
            <input name="meta_title" value="{{ old('meta_title', $post->meta_title) }}" placeholder="عنوان متا"
                   class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
            <textarea name="meta_description" rows="2" placeholder="توضیحات متا"
                      class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">{{ old('meta_description', $post->meta_description) }}</textarea>
            <input name="focus_keyword" value="{{ old('focus_keyword', $post->focus_keyword) }}" placeholder="کلمه کلیدی کانونی"
                   class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
        </div>
    </div>

    <button type="submit" class="rounded bg-neutral-900 px-4 py-2 text-sm text-white hover:bg-neutral-700">
        ذخیره
    </button>
</form>
@endsection
