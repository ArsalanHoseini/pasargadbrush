@extends('admin.layout')

@section('title', 'صفحات ثابت')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-lg font-semibold">صفحات ثابت</h1>
    <a href="{{ route('admin.pages.create') }}" class="rounded bg-neutral-900 px-4 py-2 text-sm text-white hover:bg-neutral-700">
        صفحه جدید
    </a>
</div>

@if ($pages->isEmpty())
    <p class="text-sm text-neutral-500">هنوز هیچ صفحه‌ای ثبت نشده است.</p>
@else
    <div class="overflow-x-auto rounded-lg border border-neutral-200 bg-white">
        <table class="w-full text-sm">
            <thead class="border-b border-neutral-200 bg-neutral-50 text-right">
                <tr>
                    <th class="px-4 py-3 font-medium">عنوان</th>
                    <th class="px-4 py-3 font-medium">نامک</th>
                    <th class="px-4 py-3 font-medium">قالب</th>
                    <th class="px-4 py-3 font-medium">وضعیت</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @foreach ($pages as $page)
                    <tr>
                        <td class="px-4 py-3">{{ $page->title }}</td>
                        <td class="px-4 py-3 text-neutral-500" dir="ltr">/{{ $page->slug }}</td>
                        <td class="px-4 py-3 text-neutral-500">{{ $page->template }}</td>
                        <td class="px-4 py-3">
                            @if ($page->status === 'published')
                                <span class="rounded bg-green-50 px-2 py-0.5 text-xs text-green-700">منتشرشده</span>
                            @else
                                <span class="rounded bg-neutral-100 px-2 py-0.5 text-xs text-neutral-500">پیش‌نویس</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-left">
                            @if ($page->status === 'published')
                                <a href="{{ route('pages.show', $page) }}" target="_blank" class="text-neutral-600 hover:text-neutral-900">مشاهده</a>
                            @endif
                            <a href="{{ route('admin.pages.edit', $page) }}" class="mr-2 text-neutral-600 hover:text-neutral-900">ویرایش</a>
                            <form method="POST" action="{{ route('admin.pages.destroy', $page) }}" class="inline"
                                  onsubmit="return confirm('حذف شود؟');">
                                @csrf @method('DELETE')
                                <button type="submit" class="mr-2 text-red-600 hover:text-red-800">حذف</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $pages->links() }}</div>
@endif
@endsection
