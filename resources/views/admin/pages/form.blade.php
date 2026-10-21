@extends('admin.layout')

@section('title', $page->exists ? 'ویرایش صفحه' : 'صفحه جدید')

@section('content')
<h1 class="mb-6 text-lg font-semibold">{{ $page->exists ? 'ویرایش صفحه' : 'صفحه جدید' }}</h1>

@if ($errors->any())
    <div class="mb-6 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<form method="POST"
      action="{{ $page->exists ? route('admin.pages.update', $page) : route('admin.pages.store') }}"
      class="max-w-2xl space-y-4 rounded-lg border border-neutral-200 bg-white p-6">
    @csrf
    @if ($page->exists) @method('PUT') @endif

    <div>
        <label class="mb-1 block text-sm font-medium">عنوان</label>
        <input name="title" value="{{ old('title', $page->title) }}" required
               class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium">نامک (slug) — خالی بگذارید تا خودکار ساخته شود</label>
        <input name="slug" value="{{ old('slug', $page->slug) }}" dir="ltr"
               class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
    </div>

    <div class="flex gap-4">
        <div class="flex-1">
            <label class="mb-1 block text-sm font-medium">قالب</label>
            <select name="template" class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
                <option value="default" @selected(old('template', $page->template ?? 'default') === 'default')>معمولی</option>
                <option value="about" @selected(old('template', $page->template) === 'about')>درباره ما</option>
                <option value="contact" @selected(old('template', $page->template) === 'contact')>تماس با ما (نمایش اطلاعات تماس و فرم)</option>
            </select>
        </div>
        <div class="flex-1">
            <label class="mb-1 block text-sm font-medium">وضعیت</label>
            <select name="status" class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
                <option value="draft" @selected(old('status', $page->status ?? 'draft') === 'draft')>پیش‌نویس</option>
                <option value="published" @selected(old('status', $page->status) === 'published')>منتشرشده</option>
            </select>
        </div>
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium">متن صفحه</label>
        <textarea name="body" rows="14" required
                  class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">{{ old('body', $page->body) }}</textarea>
        <p class="mt-1 text-xs text-neutral-500">
            برای قالب «تماس با ما» این متن به‌صورت مقدمه، بالای اطلاعات تماس (که خودکار از تنظیمات سایت خوانده می‌شود) نمایش داده می‌شود.
        </p>
    </div>

    <div class="border-t border-neutral-100 pt-4">
        <p class="mb-2 text-sm font-medium text-neutral-500">SEO (اختیاری)</p>
        <div class="space-y-3">
            <input name="meta_title" value="{{ old('meta_title', $page->meta_title) }}" placeholder="عنوان متا"
                   class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
            <textarea name="meta_description" rows="2" placeholder="توضیحات متا"
                      class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">{{ old('meta_description', $page->meta_description) }}</textarea>
        </div>
    </div>

    <button type="submit" class="rounded bg-neutral-900 px-4 py-2 text-sm text-white hover:bg-neutral-700">
        ذخیره
    </button>
</form>
@endsection
