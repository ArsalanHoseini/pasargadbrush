@extends('admin.layout')

@section('title', $redirect->exists ? 'ویرایش ریدایرکت' : 'ریدایرکت جدید')

@section('content')
<h1 class="mb-6 text-xl font-bold text-neutral-900">{{ $redirect->exists ? 'ویرایش ریدایرکت' : 'ریدایرکت جدید' }}</h1>

<x-admin.form-errors />

<form method="POST" action="{{ $redirect->exists ? route('admin.redirects.update', $redirect) : route('admin.redirects.store') }}"
      class="max-w-2xl space-y-4 admin-card p-6"
      x-data="{ code: @js((string) old('status_code', $redirect->status_code)) }">
    @csrf
    @if ($redirect->exists) @method('PUT') @endif

    @if ($redirect->exists && $redirect->source !== \App\Models\SeoRedirect::SOURCE_MANUAL)
        <p class="rounded bg-amber-50 p-3 text-xs text-amber-800">
            این ریدایرکت {{ \App\Models\SeoRedirect::SOURCE_LABELS[$redirect->source] }} ساخته شده است. پس از ذخیره «دستی» حساب می‌شود و اجرای دوباره‌ی تولید خودکار آن را تغییر نمی‌دهد.
        </p>
    @endif

    <div>
        <label for="from_path" class="admin-label">آدرس قدیمی</label>
        <input id="from_path" name="from_path" value="{{ old('from_path', $redirect->from_path) }}" required dir="ltr" placeholder="/product/برس-قدیمی"
               class="admin-input">
        <p class="mt-1 text-xs text-neutral-500">فقط مسیر، بدون دامنه. می‌توانید آدرس کامل را هم بچسبانید؛ دامنه و / انتهایی خودکار حذف می‌شود.</p>
    </div>

    <div>
        <label for="status_code" class="admin-label">نوع</label>
        <select id="status_code" name="status_code" x-model="code" class="admin-input sm:w-80">
            <option value="301">301 — انتقال دائمی (پیشنهادی)</option>
            <option value="302">302 — انتقال موقت</option>
            <option value="410">410 — این صفحه برای همیشه حذف شده</option>
        </select>
    </div>

    <div x-show="code !== '410'">
        <label for="to_path" class="admin-label">آدرس جدید</label>
        <input id="to_path" name="to_path" value="{{ old('to_path', $redirect->to_path) }}" dir="ltr" placeholder="/products/برس-جدید"
               :required="code !== '410'" class="admin-input">
    </div>

    <div>
        <label for="notes" class="admin-label">یادداشت (اختیاری)</label>
        <input id="notes" name="notes" value="{{ old('notes', $redirect->notes) }}" maxlength="255"
               class="admin-input">
    </div>

    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $redirect->is_active ?? true))>
        فعال
    </label>

    <div class="flex items-center gap-4">
        <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-accent-dark px-5 py-2 text-sm font-medium text-white hover:bg-brand-accent-darker">ذخیره</button>
        <a href="{{ route('admin.redirects.index') }}" class="text-sm text-neutral-500 hover:text-neutral-900">انصراف</a>
    </div>
</form>

@if ($redirect->exists)
    <form method="POST" action="{{ route('admin.redirects.destroy', $redirect) }}" class="mt-4" data-confirm="این ریدایرکت حذف شود؟">
        @csrf
        @method('DELETE')
        <button type="submit" class="text-sm text-red-600 hover:underline">حذف این ریدایرکت</button>
    </form>
@endif
@endsection
