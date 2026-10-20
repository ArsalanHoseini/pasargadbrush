@extends('admin.layout')

@section('title', 'دسته‌بندی محصولات')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-lg font-semibold">دسته‌بندی محصولات</h1>
    <a href="{{ route('admin.categories.create') }}" class="rounded bg-neutral-900 px-4 py-2 text-sm text-white hover:bg-neutral-700">
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
                    <th class="px-4 py-3 font-medium">والد</th>
                    <th class="px-4 py-3 font-medium">تعداد محصول</th>
                    <th class="px-4 py-3 font-medium">وضعیت</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @foreach ($categories as $category)
                    <tr>
                        <td class="px-4 py-3">{{ $category->name }}</td>
                        <td class="px-4 py-3 text-neutral-500">{{ $category->parent?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-neutral-500">{{ $category->primary_products_count }}</td>
                        <td class="px-4 py-3">
                            @if ($category->is_active)
                                <span class="rounded bg-green-50 px-2 py-0.5 text-xs text-green-700">فعال</span>
                            @else
                                <span class="rounded bg-neutral-100 px-2 py-0.5 text-xs text-neutral-500">غیرفعال</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-left">
                            <a href="{{ route('admin.categories.edit', $category) }}" class="text-neutral-600 hover:text-neutral-900">ویرایش</a>
                            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="inline"
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
