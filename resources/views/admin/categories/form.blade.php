@extends('admin.layout')

@section('title', $category->exists ? 'ویرایش دسته' : 'دسته جدید')

@section('content')
<h1 class="mb-6 text-xl font-bold text-neutral-900">{{ $category->exists ? 'ویرایش دسته' : 'دسته جدید' }}</h1>

<x-admin.form-errors />

<form method="POST"
      action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}"
      class="max-w-xl space-y-4 admin-card p-6">
    @csrf
    @if ($category->exists) @method('PUT') @endif

    <div>
        <label class="admin-label">نام</label>
        <input name="name" value="{{ old('name', $category->name) }}" required
               class="admin-input">
    </div>

    <div>
        <label class="admin-label">نامک (slug) — خالی بگذارید تا خودکار ساخته شود</label>
        <input name="slug" value="{{ old('slug', $category->slug) }}" dir="ltr"
               class="admin-input">
    </div>

    <div>
        <label class="admin-label">دسته والد</label>
        <select name="parent_id" class="admin-input">
            <option value="">— بدون والد —</option>
            @foreach ($parents as $parent)
                <option value="{{ $parent->id }}" @selected((int) old('parent_id', $category->parent_id) === $parent->id)>
                    {{ $parent->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="admin-label">توضیحات</label>
        <textarea name="description" rows="3" class="admin-input">{{ old('description', $category->description) }}</textarea>
    </div>

    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active ?? true))>
        فعال و نمایان در سایت
    </label>

    <div class="space-y-4 border-t border-neutral-100 pt-4">
        <p class="text-sm font-semibold text-neutral-900">سئو (اختیاری)</p>
        <x-admin.counted-field name="meta_title" label="عنوان سئو (Meta title)" :value="old('meta_title', $category->meta_title)" :min="30" :max="60" />
        <x-admin.counted-field name="meta_description" label="توضیحات متا (Meta description)" :value="old('meta_description', $category->meta_description)" :min="120" :max="160" textarea :rows="3" />
    </div>

    <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-accent-dark px-5 py-2 text-sm font-medium text-white hover:bg-brand-accent-darker">
        ذخیره
    </button>
</form>
@endsection
