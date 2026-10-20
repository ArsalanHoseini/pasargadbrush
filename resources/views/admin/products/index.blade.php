@extends('admin.layout')

@section('title', 'محصولات')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-lg font-semibold">محصولات</h1>
    <a href="{{ route('admin.products.create') }}" class="rounded bg-neutral-900 px-4 py-2 text-sm text-white hover:bg-neutral-700">
        محصول جدید
    </a>
</div>

@if ($products->isEmpty())
    <p class="text-sm text-neutral-500">هنوز هیچ محصولی ثبت نشده است.</p>
@else
    <div class="overflow-x-auto rounded-lg border border-neutral-200 bg-white">
        <table class="w-full text-sm">
            <thead class="border-b border-neutral-200 bg-neutral-50 text-right">
                <tr>
                    <th class="px-4 py-3 font-medium">نام</th>
                    <th class="px-4 py-3 font-medium">SKU</th>
                    <th class="px-4 py-3 font-medium">دسته</th>
                    <th class="px-4 py-3 font-medium">نوع</th>
                    <th class="px-4 py-3 font-medium">وضعیت</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @foreach ($products as $product)
                    <tr>
                        <td class="px-4 py-3">{{ $product->name }}</td>
                        <td class="px-4 py-3 text-neutral-500" dir="ltr">{{ $product->sku }}</td>
                        <td class="px-4 py-3 text-neutral-500">{{ $product->primaryCategory?->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @if ($product->type === 'priced')
                                <span class="rounded bg-blue-50 px-2 py-0.5 text-xs text-blue-700">قیمت‌دار</span>
                            @else
                                <span class="rounded bg-amber-50 px-2 py-0.5 text-xs text-amber-700">استعلامی</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($product->is_active)
                                <span class="rounded bg-green-50 px-2 py-0.5 text-xs text-green-700">فعال</span>
                            @else
                                <span class="rounded bg-neutral-100 px-2 py-0.5 text-xs text-neutral-500">غیرفعال</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-left">
                            <a href="{{ route('admin.products.edit', $product) }}" class="text-neutral-600 hover:text-neutral-900">ویرایش</a>
                            <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="inline"
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

    <div class="mt-6">{{ $products->links() }}</div>
@endif
@endsection
