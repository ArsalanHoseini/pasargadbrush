@extends('admin.layout')

@section('title', 'صفحات ثابت')

@section('content')
<x-admin.page-header title="صفحات ثابت">
    <x-slot:actions>
        <x-admin.button :href="route('admin.pages.create')" icon="plus">صفحه جدید</x-admin.button>
    </x-slot:actions>
</x-admin.page-header>

<x-admin.filter-bar :action="route('admin.pages.index')" placeholder="عنوان یا نامک صفحه…" />

@if ($pages->isEmpty())
    <x-admin.empty-state icon="document">{{ request()->hasAny(['q']) ? 'موردی با این فیلترها پیدا نشد.' : 'هنوز هیچ صفحه‌ای ثبت نشده است.' }}</x-admin.empty-state>
@else
    <div class="overflow-x-auto rounded-xl border border-neutral-200 bg-white">
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
                                  data-confirm="حذف شود؟">
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
