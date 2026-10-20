@extends('admin.layout')

@section('title', 'دسته‌بندی بلاگ')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-lg font-semibold">دسته‌بندی بلاگ</h1>
    <a href="{{ route('admin.blog-categories.create') }}" class="rounded bg-neutral-900 px-4 py-2 text-sm text-white hover:bg-neutral-700">
        دسته جدید
    </a>
</div>

@if ($categories->isEmpty())
    <p class="text-sm text-neutral-500">هنوز هیچ دسته‌بندی‌ای ثبت نشده است.</p>
@else
    <div class="overflow-x-auto rounded-lg border border-neutral-200 bg-white">
        <table class="w-full text-sm">
            <thead class="border-b border-neutral-200 bg-neutral-50 text-right">
                <tr>
                    <th class="px-4 py-3 font-medium">نام</th>
                    <th class="px-4 py-3 font-medium">تعداد مقاله</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @foreach ($categories as $category)
                    <tr>
                        <td class="px-4 py-3">{{ $category->name }}</td>
                        <td class="px-4 py-3 text-neutral-500">{{ $category->posts_count }}</td>
                        <td class="px-4 py-3 text-left">
                            <a href="{{ route('admin.blog-categories.edit', $category) }}" class="text-neutral-600 hover:text-neutral-900">ویرایش</a>
                            <form method="POST" action="{{ route('admin.blog-categories.destroy', $category) }}" class="inline"
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

    <div class="mt-6">{{ $categories->links() }}</div>
@endif
@endsection
