@extends('admin.layout')

@section('title', $category->exists ? 'ویرایش دسته بلاگ' : 'دسته بلاگ جدید')

@section('content')
<h1 class="mb-6 text-xl font-bold text-neutral-900">{{ $category->exists ? 'ویرایش دسته بلاگ' : 'دسته بلاگ جدید' }}</h1>

<x-admin.form-errors />

<form method="POST"
      action="{{ $category->exists ? route('admin.blog-categories.update', $category) : route('admin.blog-categories.store') }}"
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
        <label class="admin-label">توضیحات</label>
        <textarea name="description" rows="3" class="admin-input">{{ old('description', $category->description) }}</textarea>
    </div>

    <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-accent-dark px-5 py-2 text-sm font-medium text-white hover:bg-brand-accent-darker">
        ذخیره
    </button>
</form>
@endsection
