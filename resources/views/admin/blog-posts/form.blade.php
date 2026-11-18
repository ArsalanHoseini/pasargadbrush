@extends('admin.layout')

@section('title', $post->exists ? 'ویرایش مقاله' : 'مقاله جدید')

@section('content')
<x-admin.page-header :title="$post->exists ? 'ویرایش مقاله' : 'مقاله جدید'"
                     :description="$post->exists ? $post->title : 'متن، تصویر شاخص و تنظیمات سئوی مقاله را وارد کنید.'">
    <x-slot:actions>
        <x-admin.button variant="secondary" :href="route('admin.blog-posts.index')">بازگشت به مقالات</x-admin.button>
    </x-slot:actions>
</x-admin.page-header>

<x-admin.form-errors />

<form method="POST"
      action="{{ $post->exists ? route('admin.blog-posts.update', $post) : route('admin.blog-posts.store') }}">
    @csrf
    @if ($post->exists) @method('PUT') @endif

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Main column --}}
        <div class="space-y-6 lg:col-span-2">
            <div class="admin-card">
                <div class="admin-card-body">
                    <x-admin.counted-field name="title" label="عنوان مقاله (H1)" :value="old('title', $post->title)" :min="30" :max="70" required
                                           hint="این عنوان، تیتر اصلی (H1) صفحه است. در متن مقاله از H2 و H3 برای بخش‌ها استفاده کنید." />

                    <div>
                        <label for="field-slug" class="admin-label">نامک (آدرس صفحه)</label>
                        <div class="flex items-stretch overflow-hidden rounded-lg border border-neutral-300 focus-within:border-brand-accent focus-within:ring-2 focus-within:ring-brand-accent/20" dir="ltr">
                            <span class="flex items-center bg-neutral-50 px-3 text-xs text-neutral-500">{{ url('blog') }}/</span>
                            <input id="field-slug" name="slug" value="{{ old('slug', $post->slug) }}" class="w-full border-0 px-3 py-2 text-sm focus:outline-none" placeholder="auto">
                        </div>
                        <p class="admin-hint">خالی بگذارید تا از روی عنوان ساخته شود.</p>
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <div class="admin-card-header">
                    <h2 class="admin-card-title">متن مقاله</h2>
                    <span class="text-xs text-neutral-500">تیترها از فهرست «پاراگراف» نوار ابزار — تصویر از دکمه‌ی تصویر</span>
                </div>
                <div class="p-5">
                    <x-rich-text-editor name="body" :value="old('body', $post->body)" />
                </div>
            </div>

            <div class="admin-card">
                <div class="admin-card-body">
                    <x-admin.counted-field name="excerpt" label="خلاصه مقاله" :value="old('excerpt', $post->excerpt)" :min="100" :max="300" textarea :rows="3"
                                           hint="در فهرست مقالات و کارت‌ها نمایش داده می‌شود؛ اگر توضیحات متا خالی باشد، گوگل هم از آن استفاده می‌کند." />
                </div>
            </div>

            <x-admin.seo-card :meta-title="old('meta_title', $post->meta_title)" :meta-description="old('meta_description', $post->meta_description)"
                              with-focus-keyword :focus-keyword="old('focus_keyword', $post->focus_keyword)"
                              :url-prefix="url('blog').'/'" :slug="$post->slug" />
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            <div class="admin-card">
                <div class="admin-card-header"><h2 class="admin-card-title">انتشار</h2></div>
                <div class="admin-card-body">
                    <div>
                        <label for="field-status" class="admin-label">وضعیت</label>
                        <select id="field-status" name="status" class="admin-input">
                            <option value="draft" @selected(old('status', $post->status ?? 'draft') === 'draft')>پیش‌نویس (نمایش داده نمی‌شود)</option>
                            <option value="published" @selected(old('status', $post->status) === 'published')>منتشرشده</option>
                        </select>
                    </div>

                    {{-- The browser's date picker is Gregorian (it can't be changed); show the Persian date live beside it. --}}
                    <div x-data="{
                             value: @js(old('published_at', $post->published_at?->format('Y-m-d\TH:i'))),
                             get persian() {
                                 if (! this.value) return 'هم‌اکنون';
                                 // Same wall-clock time as typed (Tehran, like the app), only converted to the Persian calendar.
                                 const date = new Date(this.value + ':00Z');
                                 return isNaN(date) ? '' : new Intl.DateTimeFormat('fa-IR-u-ca-persian', { dateStyle: 'long', timeStyle: 'short', timeZone: 'UTC' }).format(date);
                             },
                         }">
                        <label for="published_at" class="admin-label">تاریخ انتشار</label>
                        <input id="published_at" type="datetime-local" name="published_at" dir="ltr" x-model="value"
                               value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}" class="admin-input">
                        <p class="admin-hint">به تاریخ شمسی: <span class="font-medium text-neutral-700" x-text="persian">{{ $post->published_at ? persian_date($post->published_at) : 'هم‌اکنون' }}</span> — خالی = هم‌اکنون، تاریخ آینده = زمان‌بندی‌شده.</p>
                    </div>

                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="featured" value="1" class="admin-check" @checked(old('featured', $post->featured))>
                        مقاله ویژه (نمایش برجسته)
                    </label>
                </div>
            </div>

            <div class="admin-card">
                <div class="admin-card-header"><h2 class="admin-card-title">تصویر شاخص</h2></div>
                <div class="p-5">
                    <x-admin.media-picker name="featured_media_id" :items="$featuredImageItems" empty-text="تصویر شاخص انتخاب نشده است." />
                    <p class="admin-hint">پیشنهاد: افقی، حداقل ۱۲۰۰×۶۳۰ پیکسل (برای اشتراک‌گذاری در شبکه‌های اجتماعی هم استفاده می‌شود).</p>
                </div>
            </div>

            <div class="admin-card">
                <div class="admin-card-header"><h2 class="admin-card-title">دسته و برچسب‌ها</h2></div>
                <div class="admin-card-body">
                    <div>
                        <label for="field-category" class="admin-label">دسته</label>
                        <select id="field-category" name="blog_category_id" class="admin-input">
                            <option value="">— بدون دسته —</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((int) old('blog_category_id', $post->blog_category_id) === $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="field-tags" class="admin-label">برچسب‌ها</label>
                        <input id="field-tags" name="tags" value="{{ old('tags', $currentTags) }}" class="admin-input" placeholder="برس صنعتی، برس سیمی">
                        <p class="admin-hint">با کاما یا «،» جدا کنید.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-admin.save-bar :cancel="route('admin.blog-posts.index')"
                      :view="$post->exists && $post->status === 'published' ? route('blog.show', $post) : null"
                      :label="$post->exists ? 'ذخیره تغییرات' : 'ایجاد مقاله'" />
</form>
@endsection
