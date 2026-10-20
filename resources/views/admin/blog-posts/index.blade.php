@extends('admin.layout')

@section('title', 'مقالات بلاگ')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-lg font-semibold">مقالات بلاگ</h1>
    <a href="{{ route('admin.blog-posts.create') }}" class="rounded bg-neutral-900 px-4 py-2 text-sm text-white hover:bg-neutral-700">
        مقاله جدید
    </a>
</div>

@if ($posts->isEmpty())
    <p class="text-sm text-neutral-500">هنوز هیچ مقاله‌ای ثبت نشده است.</p>
@else
    <div class="overflow-x-auto rounded-lg border border-neutral-200 bg-white">
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
                        <td class="px-4 py-3 text-neutral-500">{{ $post->published_at?->format('Y-m-d') ?? '—' }}</td>
                        <td class="px-4 py-3 text-left">
                            <a href="{{ route('admin.blog-posts.edit', $post) }}" class="text-neutral-600 hover:text-neutral-900">ویرایش</a>
                            <form method="POST" action="{{ route('admin.blog-posts.destroy', $post) }}" class="inline"
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

    <div class="mt-6">{{ $posts->links() }}</div>
@endif
@endsection
