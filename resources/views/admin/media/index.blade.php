@extends('admin.layout')

@section('title', 'کتابخانه مدیا')

@section('content')
<h1 class="mb-6 text-lg font-semibold">کتابخانه مدیا</h1>

@if ($errors->any())
    <div class="mb-6 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data"
      class="mb-8 rounded-lg border border-neutral-200 bg-white p-6">
    @csrf
    <div class="flex flex-wrap items-end gap-4">
        <div>
            <label for="file" class="mb-1 block text-sm font-medium">انتخاب فایل (jpg, png, webp — حداکثر ۵ مگابایت)</label>
            <input id="file" name="file" type="file" accept=".jpg,.jpeg,.png,.webp" required
                   class="text-sm">
        </div>
        <div class="grow">
            <label for="alt_text" class="mb-1 block text-sm font-medium">متن جایگزین تصویر (اختیاری)</label>
            <input id="alt_text" name="alt_text" type="text" value="{{ old('alt_text') }}"
                   class="w-full rounded border border-neutral-300 px-3 py-2 text-sm focus:border-neutral-500 focus:outline-none">
        </div>
        <button type="submit"
                class="rounded bg-neutral-900 px-4 py-2 text-sm font-medium text-white hover:bg-neutral-700">
            آپلود
        </button>
    </div>
</form>

@if ($media->isEmpty())
    <p class="text-sm text-neutral-500">هنوز هیچ فایلی آپلود نشده است.</p>
@else
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
        @foreach ($media as $item)
            <div class="rounded-lg border border-neutral-200 bg-white p-3">
                <img src="{{ $item->url() }}" alt="{{ $item->alt_text }}"
                     class="mb-2 aspect-square w-full rounded object-cover">
                <p class="truncate text-xs text-neutral-500" title="{{ $item->path }}">{{ basename($item->path) }}</p>
                <p class="mb-2 text-xs text-neutral-400">{{ number_format($item->size / 1024, 0) }} کیلوبایت</p>
                <form method="POST" action="{{ route('admin.media.destroy', $item) }}"
                      onsubmit="return confirm('این فایل حذف شود؟');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-xs text-red-600 hover:text-red-800">حذف</button>
                </form>
            </div>
        @endforeach
    </div>

    <div class="mt-6">
        {{ $media->links() }}
    </div>
@endif
@endsection
