@extends('admin.layout')

@section('title', 'کاربردها')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-2">
    <div>
        <h1 class="text-lg font-semibold">کاربردها</h1>
        <p class="text-xs text-neutral-500">کاربردها (مثلاً «شستشوی میوه») یک بار اینجا تعریف می‌شوند و در صفحه‌ی ویرایش هر محصول انتخاب می‌شوند.</p>
    </div>
    <a href="{{ route('admin.applications.create') }}" class="rounded bg-neutral-900 px-4 py-2 text-sm text-white hover:bg-neutral-700">+ کاربرد جدید</a>
</div>

@if ($applications->isEmpty())
    <p class="text-sm text-neutral-500">هنوز کاربردی تعریف نشده است.</p>
@else
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($applications as $application)
            <div class="flex items-center gap-3 rounded-lg border border-neutral-200 bg-white p-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded bg-neutral-50">
                    @if ($application->icon)
                        <img src="{{ $application->icon->url() }}" alt="" class="h-10 w-10 object-contain">
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium">{{ $application->name }}</p>
                    <p class="text-xs text-neutral-500">{{ $application->products_count }} محصول</p>
                </div>
                <div class="flex shrink-0 gap-3 text-xs">
                    <a href="{{ route('admin.applications.edit', $application) }}" class="text-blue-700 hover:underline">ویرایش</a>
                    <form method="POST" action="{{ route('admin.applications.destroy', $application) }}" onsubmit="return confirm('این کاربرد حذف شود؟');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:underline">حذف</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
