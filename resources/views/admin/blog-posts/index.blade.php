@extends('admin.layout')

@section('title', 'مقالات بلاگ')

@section('content')
<x-admin.page-header title="مقالات بلاگ" :description="$posts->total().' مقاله'">
    <x-slot:actions>
        <x-admin.button :href="route('admin.blog-posts.create')" icon="plus">مقاله جدید</x-admin.button>
    </x-slot:actions>
</x-admin.page-header>

<x-admin.filter-bar :action="route('admin.blog-posts.index')" placeholder="عنوان مقاله…">
    <x-admin.filter-select name="category" label="دسته" :options="$categoryOptions" />
    <x-admin.filter-select name="status" label="وضعیت" :options="['published' => 'منتشرشده', 'draft' => 'پیش‌نویس']" />
</x-admin.filter-bar>

@if ($posts->isEmpty())
    <x-admin.empty-state icon="document-text">{{ request()->hasAny(['q', 'category', 'status']) ? 'موردی با این فیلترها پیدا نشد.' : 'هنوز هیچ مقاله‌ای ثبت نشده است.' }}</x-admin.empty-state>
@else
    <div class="overflow-x-auto rounded-xl border border-neutral-200 bg-white">
        <table class="w-full text-sm">
            <thead class="border-b border-neutral-200 bg-neutral-50 text-right">
                <tr>
                    <th class="px-4 py-3 font-medium">عنوان</th>
                    <th class="px-4 py-3 font-medium">دسته</th>
                    <th class="px-4 py-3 font-medium">وضعیت</th>
                    <th class="px-4 py-3 font-medium">تاریخ انتشار</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @foreach ($posts as $post)
                    <tr>
                        <td class="px-4 py-3">{{ $post->title }}</td>
                        <td class="px-4 py-3 text-neutral-500">{{ $post->category?->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @if ($post->status === 'published')
                                <span class="rounded bg-green-50 px-2 py-0.5 text-xs text-green-700">منتشرشده</span>
                            @else
                                <span class="rounded bg-neutral-100 px-2 py-0.5 text-xs text-neutral-500">پیش‌نویس</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-neutral-500">{{ $post->published_at ? persian_date($post->published_at, \App\Support\PersianDate::COMPACT_DATE) : '—' }}</td>
                        <td class="px-4 py-3 text-left">
                            <a href="{{ route('admin.blog-posts.edit', $post) }}" class="text-neutral-600 hover:text-neutral-900">ویرایش</a>
                            <form method="POST" action="{{ route('admin.blog-posts.destroy', $post) }}" class="inline"
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

    <x-admin.pagination :paginator="$posts" />
@endif
@endsection
