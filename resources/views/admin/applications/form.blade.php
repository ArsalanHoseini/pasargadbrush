@extends('admin.layout')

@section('title', $application->exists ? 'ویرایش کاربرد' : 'کاربرد جدید')

@section('content')
<h1 class="mb-6 text-xl font-bold text-neutral-900">{{ $application->exists ? 'ویرایش کاربرد' : 'کاربرد جدید' }}</h1>

<x-admin.form-errors />

<form method="POST" enctype="multipart/form-data"
      action="{{ $application->exists ? route('admin.applications.update', $application) : route('admin.applications.store') }}"
      class="max-w-lg space-y-4 admin-card p-6">
    @csrf
    @if ($application->exists) @method('PUT') @endif

    <div>
        <label for="name" class="admin-label">عنوان (مثلاً شستشوی فرش)</label>
        <input id="name" name="name" value="{{ old('name', $application->name) }}" required maxlength="100"
               class="admin-input">
    </div>

    <div>
        <label for="icon" class="admin-label">آیکون</label>
        @if ($application->icon)
            <img src="{{ $application->icon->url() }}" alt="آیکون فعلی" class="mb-2 h-12 w-12 rounded border border-neutral-200 object-contain p-1">
        @endif
        <input id="icon" type="file" name="icon" accept="image/png,image/webp,image/jpeg"
               class="block w-full text-sm file:me-3 file:rounded file:border-0 file:bg-neutral-900 file:px-3 file:py-1.5 file:text-xs file:text-white">
        <p class="mt-1 text-xs text-neutral-500">PNG یا WEBP با پس‌زمینه‌ی شفاف، مربعی، حداکثر ۱ مگابایت.{{ $application->icon ? ' انتخاب فایل جدید، آیکون فعلی را جایگزین می‌کند.' : '' }}</p>
    </div>

    <div class="w-32">
        <label for="sort_order" class="admin-label">ترتیب نمایش</label>
        <input id="sort_order" type="number" min="0" name="sort_order" value="{{ old('sort_order', $application->sort_order ?? 0) }}" dir="ltr"
               class="admin-input">
    </div>

    <div class="flex items-center gap-4">
        <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-accent-dark px-5 py-2 text-sm font-medium text-white hover:bg-brand-accent-darker">ذخیره</button>
        <a href="{{ route('admin.applications.index') }}" class="text-sm text-neutral-500 hover:text-neutral-900">انصراف</a>
    </div>
</form>
@endsection
