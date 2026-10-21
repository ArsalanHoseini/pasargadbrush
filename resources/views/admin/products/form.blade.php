@extends('admin.layout')

@section('title', $product->exists ? 'ویرایش محصول' : 'محصول جدید')

@section('content')
<h1 class="mb-6 text-lg font-semibold">{{ $product->exists ? 'ویرایش محصول' : 'محصول جدید' }}</h1>

@if ($errors->any())
    <div class="mb-6 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<form method="POST"
      action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}"
      class="max-w-3xl space-y-6"
      x-data="{
          type: '{{ old('type', $product->type ?? 'quote') }}',
          variants: {{ json_encode(old('variants', $variants->map(fn ($v) => [
              'id' => $v->id,
              'label' => $v->label,
              'price_toman' => intdiv($v->price_rial, 10),
              'stock_quantity' => $v->stock_quantity,
              'is_default' => $v->is_default,
          ])->values())) }},
          specifications: {{ json_encode(old('specifications', $specificationRows)) }},
      }">
    @csrf
    @if ($product->exists) @method('PUT') @endif

    <div class="rounded-lg border border-neutral-200 bg-white p-6 space-y-4">
        <div class="flex gap-4">
            <div class="flex-1">
                <label class="mb-1 block text-sm font-medium">نام محصول</label>
                <input name="name" value="{{ old('name', $product->name) }}" required
                       class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
            </div>
            <div class="w-40">
                <label class="mb-1 block text-sm font-medium">SKU</label>
                <input name="sku" value="{{ old('sku', $product->sku) }}" dir="ltr" required
                       class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">نامک (slug) — خالی بگذارید تا خودکار ساخته شود</label>
            <input name="slug" value="{{ old('slug', $product->slug) }}" dir="ltr"
                   class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
        </div>

        <div class="flex gap-4">
            <div class="flex-1">
                <label class="mb-1 block text-sm font-medium">دسته اصلی</label>
                <select name="primary_category_id" class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
                    <option value="">— انتخاب کنید —</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((int) old('primary_category_id', $product->primary_category_id) === $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1">
                <label class="mb-1 block text-sm font-medium">دسته‌های دیگر (اختیاری، چندتایی)</label>
                <select name="category_ids[]" multiple class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(in_array($category->id, old('category_ids', $selectedCategoryIds)))>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">نوع فروش</label>
            <div class="flex gap-4 text-sm">
                <label class="flex items-center gap-2">
                    <input type="radio" name="type" value="quote" x-model="type">
                    استعلامی (بدون قیمت — درخواست قیمت)
                </label>
                <label class="flex items-center gap-2">
                    <input type="radio" name="type" value="priced" x-model="type">
                    قیمت‌دار (با تنوع و قیمت مشخص)
                </label>
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">توضیح کوتاه</label>
            <textarea name="short_description" rows="2" class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">{{ old('short_description', $product->short_description) }}</textarea>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">توضیحات کامل</label>
            <textarea name="description" rows="6" class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">{{ old('description', $product->description) }}</textarea>
        </div>

        <div class="flex gap-4">
            <div class="flex-1">
                <label class="mb-1 block text-sm font-medium">وضعیت موجودی (اختیاری)</label>
                <select name="stock_status" class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
                    <option value="">— نامشخص —</option>
                    <option value="in_stock" @selected(old('stock_status', $product->stock_status) === 'in_stock')>موجود</option>
                    <option value="out_of_stock" @selected(old('stock_status', $product->stock_status) === 'out_of_stock')>ناموجود</option>
                    <option value="made_to_order" @selected(old('stock_status', $product->stock_status) === 'made_to_order')>سفارشی/تولید بر اساس سفارش</option>
                </select>
            </div>
            <div class="flex flex-1 items-end gap-6 pb-2">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active ?? false))>
                    فعال و نمایان در سایت
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured ?? false))>
                    محصول ویژه
                </label>
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-neutral-200 bg-white p-6">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-sm font-medium">مشخصات فنی</h2>
            <button type="button" @click="specifications.push({key: '', value: ''})"
                    class="rounded border border-neutral-300 px-3 py-1 text-xs hover:bg-neutral-50">
                + افزودن مشخصه
            </button>
        </div>

        <template x-if="specifications.length === 0">
            <p class="text-sm text-neutral-500">مشخصه‌ای ثبت نشده است.</p>
        </template>

        <template x-for="(spec, index) in specifications" :key="index">
            <div class="mb-2 flex items-end gap-3">
                <div class="flex-1">
                    <label class="mb-1 block text-xs text-neutral-500">عنوان (مثلاً قطر)</label>
                    <input :name="`specifications[${index}][key]`" x-model="spec.key"
                           class="w-full rounded border border-neutral-300 px-2 py-1.5 text-sm">
                </div>
                <div class="flex-1">
                    <label class="mb-1 block text-xs text-neutral-500">مقدار (مثلاً ۲۰ سانتی‌متر)</label>
                    <input :name="`specifications[${index}][value]`" x-model="spec.value"
                           class="w-full rounded border border-neutral-300 px-2 py-1.5 text-sm">
                </div>
                <button type="button" @click="specifications.splice(index, 1)" class="text-xs text-red-600 hover:text-red-800">
                    حذف
                </button>
            </div>
        </template>
    </div>

    <div class="rounded-lg border border-neutral-200 bg-white p-6" x-show="type === 'priced'" x-cloak>
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-sm font-medium">تنوع‌ها (ابعاد/اندازه و قیمت)</h2>
            <button type="button"
                    @click="variants.push({id: null, label: '', price_toman: 0, stock_quantity: null, is_default: variants.length === 0})"
                    class="rounded border border-neutral-300 px-3 py-1 text-xs hover:bg-neutral-50">
                + افزودن تنوع
            </button>
        </div>

        <template x-if="variants.length === 0">
            <p class="text-sm text-neutral-500">هنوز تنوعی اضافه نشده است.</p>
        </template>

        <template x-for="(variant, index) in variants" :key="index">
            <div class="mb-3 flex flex-wrap items-end gap-3 rounded border border-neutral-100 p-3">
                <input type="hidden" :name="`variants[${index}][id]`" x-model="variant.id">
                <div class="flex-1">
                    <label class="mb-1 block text-xs text-neutral-500">برچسب (مثلاً ۴۰×۶۰ سانتی‌متر)</label>
                    <input :name="`variants[${index}][label]`" x-model="variant.label" required
                           class="w-full rounded border border-neutral-300 px-2 py-1.5 text-sm">
                </div>
                <div class="w-32">
                    <label class="mb-1 block text-xs text-neutral-500">قیمت (تومان)</label>
                    <input type="number" min="0" :name="`variants[${index}][price_toman]`" x-model="variant.price_toman" required
                           class="w-full rounded border border-neutral-300 px-2 py-1.5 text-sm" dir="ltr">
                </div>
                <div class="w-28">
                    <label class="mb-1 block text-xs text-neutral-500">موجودی</label>
                    <input type="number" min="0" :name="`variants[${index}][stock_quantity]`" x-model="variant.stock_quantity"
                           class="w-full rounded border border-neutral-300 px-2 py-1.5 text-sm" dir="ltr">
                </div>
                <label class="flex items-center gap-1 text-xs">
                    <input type="checkbox" :name="`variants[${index}][is_default]`" value="1" x-model="variant.is_default">
                    پیش‌فرض
                </label>
                <button type="button" @click="variants.splice(index, 1)" class="text-xs text-red-600 hover:text-red-800">
                    حذف
                </button>
            </div>
        </template>
    </div>

    <div class="rounded-lg border border-neutral-200 bg-white p-6">
        <h2 class="mb-3 text-sm font-medium">گالری تصاویر</h2>
        @if ($media->isEmpty())
            <p class="text-sm text-neutral-500">
                هنوز هیچ فایلی در <a href="{{ route('admin.media.index') }}" class="underline">کتابخانه مدیا</a> آپلود نشده است.
            </p>
        @else
            <div class="grid grid-cols-4 gap-3 sm:grid-cols-6">
                @foreach ($media as $item)
                    <label class="cursor-pointer">
                        <input type="checkbox" name="image_ids[]" value="{{ $item->id }}"
                               class="peer sr-only"
                               @checked(in_array($item->id, old('image_ids', $selectedImageIds)))>
                        <img src="{{ $item->url() }}" alt="{{ $item->alt_text }}"
                             class="aspect-square w-full rounded border-2 border-transparent object-cover peer-checked:border-neutral-900">
                    </label>
                @endforeach
            </div>
        @endif
    </div>

    <div class="rounded-lg border border-neutral-200 bg-white p-6">
        <p class="mb-2 text-sm font-medium text-neutral-500">SEO (اختیاری)</p>
        <div class="space-y-3">
            <input name="meta_title" value="{{ old('meta_title', $product->meta_title) }}" placeholder="عنوان متا"
                   class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
            <textarea name="meta_description" rows="2" placeholder="توضیحات متا"
                      class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">{{ old('meta_description', $product->meta_description) }}</textarea>
        </div>
    </div>

    <button type="submit" class="rounded bg-neutral-900 px-4 py-2 text-sm text-white hover:bg-neutral-700">
        ذخیره
    </button>
</form>
@endsection
