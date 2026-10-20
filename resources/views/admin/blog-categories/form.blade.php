@extends('admin.layout')

@section('title', $category->exists ? 'ویرایش دسته بلاگ' : 'دسته بلاگ جدید')

@section('content')
<h1 class="mb-6 text-lg font-semibold">{{ $category->exists ? 'ویرایش دسته بلاگ' : 'دسته بلاگ جدید' }}</h1>

@if ($errors->any())
    <div class="mb-6 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<form method="POST"
      action="{{ $category->exists ? route('admin.blog-categories.update', $category) : route('admin.blog-categories.store') }}"
      class="max-w-xl space-y-4 rounded-lg border border-neutral-200 bg-white p-6">
    @csrf
    @if ($category->exists) @method('PUT') @endif

    <div>
        <label class="mb-1 block text-sm font-medium">نام</label>
        <input name="name" value="{{ old('name', $category->name) }}" required
               class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium">نامک (slug) — خالی بگذارید تا خودکار ساخته شود</label>
        <input name="slug" value="{{ old('slug', $category->slug) }}" dir="ltr"
               class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium">توضیحات</label>
        <textarea name="description" rows="3" class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">{{ old('description', $category->description) }}</textarea>
    </div>

    <button type="submit" class="rounded bg-neutral-900 px-4 py-2 text-sm text-white hover:bg-neutral-700">
        ذخیره
    </button>
</form>
@endsection
