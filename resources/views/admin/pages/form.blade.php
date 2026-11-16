@extends('admin.layout')

@section('title', $page->exists ? 'ویرایش صفحه' : 'صفحه جدید')

@section('content')
<x-admin.page-header :title="$page->exists ? 'ویرایش صفحه' : 'صفحه جدید'" :description="$page->exists ? $page->title : null">
    <x-slot:actions>
        <x-admin.button variant="secondary" :href="route('admin.pages.index')">بازگشت به صفحات</x-admin.button>
    </x-slot:actions>
</x-admin.page-header>

<x-admin.form-errors />

<form method="POST" action="{{ $page->exists ? route('admin.pages.update', $page) : route('admin.pages.store') }}">
    @csrf
    @if ($page->exists) @method('PUT') @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="min-w-0 space-y-6 lg:col-span-2">
            <div class="admin-card">
                <div class="admin-card-body">
                    <x-admin.counted-field name="title" label="عنوان صفحه (H1)" :value="old('title', $page->title)" :min="10" :max="70" required />

                    <div>
                        <label for="field-slug" class="admin-label">نامک (آدرس صفحه)</label>
                        <div class="flex items-stretch overflow-hidden rounded-lg border border-neutral-300 focus-within:border-brand-accent focus-within:ring-2 focus-within:ring-brand-accent/20" dir="ltr">
                            <span class="flex items-center bg-neutral-50 px-3 text-xs text-neutral-500">{{ url('page') }}/</span>
                            <input id="field-slug" name="slug" value="{{ old('slug', $page->slug) }}" class="w-full border-0 px-3 py-2 text-sm focus:outline-none" placeholder="auto">
                        </div>
                        <p class="admin-hint">خالی بگذارید تا از روی عنوان ساخته شود.</p>
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <div class="admin-card-header"><h2 class="admin-card-title">متن صفحه</h2></div>
                <div class="p-5">
                    <x-rich-text-editor name="body" :value="old('body', $page->body)" />
                    <p class="admin-hint">برای قالب «تماس با ما» این متن به‌صورت مقدمه، بالای اطلاعات تماس (که خودکار از تنظیمات سایت خوانده می‌شود) نمایش داده می‌شود.</p>
                </div>
            </div>

            <x-admin.seo-card :meta-title="old('meta_title', $page->meta_title)" :meta-description="old('meta_description', $page->meta_description)"
                              :url-prefix="url('page').'/'" :slug="$page->slug" />
        </div>

        <div class="space-y-6">
            <div class="admin-card">
                <div class="admin-card-header"><h2 class="admin-card-title">انتشار</h2></div>
                <div class="admin-card-body">
                    <div>
                        <label for="field-status" class="admin-label">وضعیت</label>
                        <select id="field-status" name="status" class="admin-input">
                            <option value="draft" @selected(old('status', $page->status ?? 'draft') === 'draft')>پیش‌نویس</option>
                            <option value="published" @selected(old('status', $page->status) === 'published')>منتشرشده</option>
                        </select>
                    </div>
                    <div>
                        <label for="field-template" class="admin-label">قالب</label>
                        <select id="field-template" name="template" class="admin-input">
                            <option value="default" @selected(old('template', $page->template ?? 'default') === 'default')>معمولی</option>
                            <option value="about" @selected(old('template', $page->template) === 'about')>درباره ما</option>
                            <option value="contact" @selected(old('template', $page->template) === 'contact')>تماس با ما (اطلاعات تماس و فرم)</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-admin.save-bar :cancel="route('admin.pages.index')"
                      :view="$page->exists && $page->status === 'published' ? route('pages.show', $page) : null"
                      :label="$page->exists ? 'ذخیره تغییرات' : 'ایجاد صفحه'" />
</form>
@endsection
