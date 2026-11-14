@extends('admin.layout')

@section('title', 'محصولات')

@section('content')
<x-admin.page-header title="محصولات" :description="$products->total().' محصول'">
    <x-slot:actions>
        <x-admin.button :href="route('admin.products.create')" icon="plus">محصول جدید</x-admin.button>
    </x-slot:actions>
</x-admin.page-header>

<x-admin.filter-bar :action="route('admin.products.index')" placeholder="نام یا SKU محصول…">
    <x-admin.filter-select name="category" label="دسته" :options="$categoryOptions" />
    <x-admin.filter-select name="type" label="نوع" :options="['priced' => 'قیمت‌دار', 'quote' => 'استعلامی']" />
    <x-admin.filter-select name="status" label="وضعیت" :options="['active' => 'فعال', 'inactive' => 'غیرفعال']" />
    <x-admin.filter-select name="stock" label="موجودی" :options="['low' => 'موجودی کم']" />
</x-admin.filter-bar>

@if ($products->isEmpty())
    <x-admin.empty-state icon="cube">
        {{ request()->hasAny(['q', 'category', 'type', 'status', 'stock']) ? 'محصولی با این فیلترها پیدا نشد.' : 'هنوز هیچ محصولی ثبت نشده است.' }}
    </x-admin.empty-state>
@else
    <div class="overflow-x-auto rounded-xl border border-neutral-200 bg-white">
        <table class="w-full text-sm">
            <thead class="border-b border-neutral-200 bg-neutral-50 text-right text-xs text-neutral-500">
                <tr>
                    <th class="px-4 py-3 font-medium">محصول</th>
                    <th class="px-4 py-3 font-medium">دسته</th>
                    <th class="px-4 py-3 font-medium">نوع / قیمت</th>
                    <th class="px-4 py-3 font-medium">موجودی</th>
                    <th class="px-4 py-3 font-medium">وضعیت</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @foreach ($products as $product)
                    @php
                        $thumbnail = $product->images->first();
                        $prices = $product->variants->pluck('price_rial')->filter();
                        $trackedStock = $product->variants->whereNotNull('stock_quantity');
                        $lowestStock = $trackedStock->min('stock_quantity');
                    @endphp
                    <tr class="hover:bg-neutral-50">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.products.edit', $product) }}" class="flex items-center gap-3">
                                @if ($thumbnail)
                                    <img src="{{ $thumbnail->thumbnailUrl(96) }}" alt="" class="size-11 shrink-0 rounded-lg border border-neutral-100 object-cover" loading="lazy">
                                @else
                                    <span class="flex size-11 shrink-0 items-center justify-center rounded-lg bg-neutral-100 text-neutral-300"><x-admin.icon name="photo" /></span>
                                @endif
                                <span class="min-w-0">
                                    <span class="block font-medium hover:text-brand-accent-dark">{{ $product->name }}</span>
                                    <span class="block text-xs text-neutral-400" dir="ltr">{{ $product->sku }}</span>
                                </span>
                            </a>
                        </td>
                        <td class="px-4 py-3 text-neutral-500">{{ $product->primaryCategory?->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @if ($product->type === 'priced')
                                <span class="rounded-full bg-blue-50 px-2 py-0.5 text-xs text-blue-700">قیمت‌دار</span>
                                @if ($prices->isNotEmpty())
                                    <span class="mt-1 block whitespace-nowrap text-xs text-neutral-500" dir="ltr">
                                        {{ $prices->min() === $prices->max() ? \App\Support\Money::toman($prices->min()) : \App\Support\Money::toman($prices->min()).' – '.\App\Support\Money::toman($prices->max()) }}
                                    </span>
                                @endif
                            @else
                                <span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs text-amber-700">استعلامی</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($trackedStock->isEmpty())
                                <span class="text-xs text-neutral-400">بدون کنترل</span>
                            @elseif ($lowestStock <= \App\Services\Admin\DashboardMetrics::LOW_STOCK_THRESHOLD)
                                <span class="rounded-full bg-red-50 px-2 py-0.5 text-xs text-red-700">{{ $lowestStock === 0 ? 'ناموجود' : 'کم: '.$lowestStock }}</span>
                            @else
                                <span class="text-xs text-neutral-600">{{ $trackedStock->sum('stock_quantity') }} عدد</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($product->is_active)
                                <span class="rounded-full bg-green-50 px-2 py-0.5 text-xs text-green-700">فعال</span>
                            @else
                                <span class="rounded-full bg-neutral-100 px-2 py-0.5 text-xs text-neutral-500">غیرفعال</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-left">
                            @if ($product->is_active)
                                <a href="{{ route('products.show', $product) }}" target="_blank" rel="noopener" class="ml-2 text-neutral-400 hover:text-neutral-900" title="مشاهده در سایت" aria-label="مشاهده در سایت">
                                    <x-admin.icon name="external" class="inline size-4" />
                                </a>
                            @endif
                            <a href="{{ route('admin.products.edit', $product) }}" class="text-neutral-600 hover:text-neutral-900">ویرایش</a>
                            <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="inline"
                                  data-confirm="محصول «{{ $product->name }}» حذف شود؟">
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
