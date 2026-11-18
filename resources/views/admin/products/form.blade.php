@extends('admin.layout')

@section('title', $product->exists ? 'ویرایش محصول' : 'محصول جدید')

@section('content')
<x-admin.page-header :title="$product->exists ? 'ویرایش محصول' : 'محصول جدید'"
                     :description="$product->exists ? $product->name : 'اطلاعات، تصاویر، قیمت و سئوی محصول را وارد کنید.'">
    <x-slot:actions>
        <x-admin.button variant="secondary" :href="route('admin.products.index')">بازگشت به محصولات</x-admin.button>
    </x-slot:actions>
</x-admin.page-header>

<x-admin.form-errors />

<form method="POST"
      action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}"
      enctype="multipart/form-data"
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
          dimensionFields: {{ json_encode(old('dimension_fields', $dimensionFields->map(fn ($f) => [
              'id' => $f->id,
              'type' => $f->type,
              'options' => implode("\n", $f->options ?? []),
              'code' => $f->code,
              'label' => $f->label,
              'unit' => $f->unit,
              'is_required' => $f->is_required,
              'min_value' => $f->min_value,
              'max_value' => $f->max_value,
          ])->values())) }},
      }">
    @csrf
    @if ($product->exists) @method('PUT') @endif

    <div class="grid gap-6 lg:grid-cols-3">
    {{-- Main column --}}
    <div class="min-w-0 space-y-6 lg:col-span-2">

    <div class="admin-card">
        <div class="admin-card-header"><h2 class="admin-card-title">اطلاعات اصلی</h2></div>
        <div class="admin-card-body">
            <div class="grid gap-4 sm:grid-cols-[1fr_10rem]">
                <x-admin.counted-field name="name" label="نام محصول (H1)" :value="old('name', $product->name)" :min="10" :max="70" required
                                       hint="نام محصول تیتر اصلی (H1) صفحه‌ی محصول است." />
                <div>
                    <label for="field-sku" class="admin-label">کد کالا (SKU) <span class="text-red-600">*</span></label>
                    <input id="field-sku" name="sku" value="{{ old('sku', $product->sku) }}" dir="ltr" required class="admin-input">
                </div>
            </div>

            <div>
                <label for="field-slug" class="admin-label">نامک (آدرس صفحه)</label>
                <div class="flex items-stretch overflow-hidden rounded-lg border border-neutral-300 focus-within:border-brand-accent focus-within:ring-2 focus-within:ring-brand-accent/20" dir="ltr">
                    <span class="flex items-center bg-neutral-50 px-3 text-xs text-neutral-500">{{ url('products') }}/</span>
                    <input id="field-slug" name="slug" value="{{ old('slug', $product->slug) }}" class="w-full border-0 px-3 py-2 text-sm focus:outline-none" placeholder="auto">
                </div>
                <p class="admin-hint">خالی بگذارید تا از روی نام ساخته شود.</p>
            </div>

            <x-admin.counted-field name="short_description" label="توضیح کوتاه" :value="old('short_description', $product->short_description)" :min="80" :max="300" textarea :rows="3"
                                   hint="زیر نام محصول و در کارت‌ها نمایش داده می‌شود." />

            <div>
                <p class="admin-label">توضیحات کامل</p>
                <x-rich-text-editor name="description" :value="old('description', $product->description)" />
            </div>
        </div>
    </div>

    <div class="admin-card p-5">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="admin-card-title">مشخصات فنی</h2>
            <button type="button" @click="specifications.push({key: '', value: ''})"
                    class="admin-chip-button">
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
                           class="w-full admin-input admin-input-sm">
                </div>
                <div class="flex-1">
                    <label class="mb-1 block text-xs text-neutral-500">مقدار (مثلاً ۲۰ سانتی‌متر)</label>
                    <input :name="`specifications[${index}][value]`" x-model="spec.value"
                           class="w-full admin-input admin-input-sm">
                </div>
                <button type="button" @click="specifications.splice(index, 1)" class="text-xs text-red-600 hover:text-red-800">
                    حذف
                </button>
            </div>
        </template>
    </div>

    <div class="admin-card p-5" x-show="type === 'priced'" x-cloak>
        <div class="mb-3 flex items-center justify-between">
            <h2 class="admin-card-title">تنوع‌ها (ابعاد/اندازه و قیمت)</h2>
            <button type="button"
                    @click="variants.push({id: null, label: '', price_toman: 0, stock_quantity: null, is_default: variants.length === 0})"
                    class="admin-chip-button">
                + افزودن تنوع
            </button>
        </div>

        <template x-if="variants.length === 0">
            <p class="text-sm text-neutral-500">هنوز تنوعی اضافه نشده است.</p>
        </template>

        <template x-for="(variant, index) in variants" :key="index">
            <div class="mb-3 flex flex-wrap items-end gap-3 rounded-lg border border-neutral-200 bg-neutral-50/60 p-3">
                <input type="hidden" :name="`variants[${index}][id]`" x-model="variant.id">
                <div class="flex-1">
                    <label class="mb-1 block text-xs text-neutral-500">برچسب (مثلاً ۴۰×۶۰ سانتی‌متر)</label>
                    <input :name="`variants[${index}][label]`" x-model="variant.label" required
                           class="w-full admin-input admin-input-sm">
                </div>
                <div class="w-32">
                    <label class="mb-1 block text-xs text-neutral-500">قیمت (تومان)</label>
                    <input type="number" min="0" :name="`variants[${index}][price_toman]`" x-model="variant.price_toman" required
                           class="w-full admin-input admin-input-sm" dir="ltr">
                </div>
                <div class="w-28">
                    <label class="mb-1 block text-xs text-neutral-500">موجودی</label>
                    <input type="number" min="0" :name="`variants[${index}][stock_quantity]`" x-model="variant.stock_quantity"
                           class="w-full admin-input admin-input-sm" dir="ltr">
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

    <div class="admin-card p-5">
        <h2 class="admin-card-title mb-1">سفارشی‌سازی — شماتیک‌ها و فرم سفارش</h2>
        <p class="mb-4 text-xs text-neutral-500">
            فیلدهای این بخش در کارت «مشخصات و سفارش» صفحه‌ی محصول نمایش داده می‌شوند و مشتری هنگام سفارش (یا استعلام قیمت) باید آن‌ها را پر کند.
            برای هر بُعد، حرفی را بنویسید که روی تصویر شماتیک آمده است. بدون هیچ فیلدی، محصول فرم سفارشی ندارد.
        </p>

        <div class="mb-5 rounded-lg border border-neutral-200 bg-neutral-50/60 p-3">
            <label class="mb-2 block text-sm font-medium">تصاویر شماتیک (کنار تصویر محصول نمایش داده می‌شوند)</label>
            @if ($product->exists && $product->schematics->isNotEmpty())
                <div class="mb-3 flex flex-wrap gap-4">
                    @foreach ($product->schematics as $schematic)
                        <div class="space-y-1">
                            <img src="{{ $schematic->url() }}" alt="شماتیک {{ $loop->iteration }}"
                                 class="h-28 w-auto rounded border border-neutral-200 bg-white object-contain p-1">
                            <label class="flex items-center gap-1 text-xs text-red-700">
                                <input type="checkbox" name="remove_schematic_ids[]" value="{{ $schematic->id }}">
                                حذف
                            </label>
                        </div>
                    @endforeach
                </div>
            @endif
            <input type="file" name="schematics[]" multiple accept="image/jpeg,image/png,image/webp"
                   class="block w-full text-sm file:me-3 file:rounded file:border-0 file:bg-neutral-900 file:px-3 file:py-1.5 file:text-xs file:text-white">
            <p class="mt-1 text-xs text-neutral-500">
                JPG، PNG یا WEBP، هر کدام حداکثر ۵ مگابایت. می‌توانید چند تصویر (مثلاً نمای جلو و برش) با هم انتخاب کنید؛ به تصاویر قبلی اضافه می‌شوند.
            </p>
        </div>

        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-sm font-medium">فیلدهایی که مشتری پر می‌کند</h3>
            <div class="flex gap-2">
                <button type="button"
                        @click="dimensionFields.push({id: null, type: 'number', options: '', code: '', label: '', unit: 'mm', is_required: true, min_value: null, max_value: null})"
                        class="admin-chip-button">
                    + بُعد (عدد)
                </button>
                <button type="button"
                        @click="dimensionFields.push({id: null, type: 'choice', options: '', code: '', label: '', unit: 'mm', is_required: true, min_value: null, max_value: null})"
                        class="admin-chip-button">
                    + گزینه‌ی انتخابی (مثلاً جنس الیاف)
                </button>
            </div>
        </div>

        <template x-if="dimensionFields.length === 0">
            <p class="text-sm text-neutral-500">فیلدی تعریف نشده — این محصول فرم سفارشی ندارد.</p>
        </template>

        <template x-for="(field, index) in dimensionFields" :key="index">
            <div class="mb-3 flex flex-wrap items-end gap-3 rounded-lg border border-neutral-200 bg-neutral-50/60 p-3">
                <input type="hidden" :name="`dimension_fields[${index}][id]`" x-model="field.id">
                <input type="hidden" :name="`dimension_fields[${index}][type]`" :value="field.type || 'number'">
                <span class="w-full text-xs font-medium text-neutral-500" x-text="field.type === 'choice' ? 'گزینه‌ی انتخابی' : 'بُعد (عدد)'"></span>
                <div class="w-20">
                    <label class="mb-1 block text-xs text-neutral-500" x-text="field.type === 'choice' ? 'حرف (اختیاری)' : 'حرف روی شکل'"></label>
                    <input :name="`dimension_fields[${index}][code]`" x-model="field.code" :required="field.type !== 'choice'" dir="ltr" placeholder="A"
                           class="w-full rounded border border-neutral-300 px-2 py-1.5 text-center text-sm font-bold">
                </div>
                <div class="min-w-40 flex-1">
                    <label class="mb-1 block text-xs text-neutral-500" x-text="field.type === 'choice' ? 'عنوان (مثلاً جنس الیاف)' : 'عنوان (مثلاً قطر خارجی)'"></label>
                    <input :name="`dimension_fields[${index}][label]`" x-model="field.label" required
                           class="w-full admin-input admin-input-sm">
                </div>
                <div class="w-full" x-show="field.type === 'choice'">
                    <label class="mb-1 block text-xs text-neutral-500">گزینه‌ها (هر گزینه در یک خط، یا جدا با کاما)</label>
                    <textarea :name="`dimension_fields[${index}][options]`" x-model="field.options" rows="3" :required="field.type === 'choice'"
                              placeholder="نایلون (PA)&#10;PET&#10;PP&#10;سایر"
                              class="w-full admin-input admin-input-sm"></textarea>
                </div>
                <div class="w-28" x-show="field.type !== 'choice'">
                    <label class="mb-1 block text-xs text-neutral-500">واحد</label>
                    <select :name="`dimension_fields[${index}][unit]`" x-model="field.unit"
                            class="w-full admin-input admin-input-sm">
                        @foreach (\App\Models\ProductDimensionField::UNITS as $unit => $unitLabel)
                            <option value="{{ $unit }}">{{ $unitLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-24" x-show="field.type !== 'choice'">
                    <label class="mb-1 block text-xs text-neutral-500">حداقل</label>
                    <input type="number" step="any" min="0" :name="`dimension_fields[${index}][min_value]`" x-model="field.min_value" dir="ltr"
                           class="w-full admin-input admin-input-sm">
                </div>
                <div class="w-24" x-show="field.type !== 'choice'">
                    <label class="mb-1 block text-xs text-neutral-500">حداکثر</label>
                    <input type="number" step="any" min="0" :name="`dimension_fields[${index}][max_value]`" x-model="field.max_value" dir="ltr"
                           class="w-full admin-input admin-input-sm">
                </div>
                <label class="flex items-center gap-1 pb-2 text-xs">
                    <input type="hidden" :name="`dimension_fields[${index}][is_required]`" value="0">
                    <input type="checkbox" :name="`dimension_fields[${index}][is_required]`" value="1" x-model="field.is_required">
                    الزامی
                </label>
                <div class="flex gap-2 pb-2 text-xs">
                    <button type="button" @click="index > 0 && dimensionFields.splice(index - 1, 0, dimensionFields.splice(index, 1)[0])"
                            class="text-neutral-500 hover:text-neutral-900" title="بالا">▲</button>
                    <button type="button" @click="index < dimensionFields.length - 1 && dimensionFields.splice(index + 1, 0, dimensionFields.splice(index, 1)[0])"
                            class="text-neutral-500 hover:text-neutral-900" title="پایین">▼</button>
                    <button type="button" @click="dimensionFields.splice(index, 1)" class="text-red-600 hover:text-red-800">حذف</button>
                </div>
            </div>
        </template>
    </div>

    <div class="admin-card p-5"
         x-data="{ highlights: {{ json_encode(old('highlights') ? array_values(array_map(fn ($row) => ['id' => $row['id'] ?? null, 'title' => $row['title'] ?? '', 'image_url' => null], old('highlights'))) : $highlights->map(fn ($highlight) => [
             'id' => $highlight->id,
             'title' => $highlight->title,
             'image_url' => $highlight->image?->url(),
         ])->values()) }} }">
        <div class="mb-1 flex items-center justify-between">
            <h2 class="admin-card-title">ویژگی‌های کلیدی (کنار تصویر محصول)</h2>
            <button type="button" x-show="highlights.length < {{ \App\Models\ProductHighlight::MAX_PER_PRODUCT }}"
                    @click="highlights.push({ id: null, title: '', image_url: null })"
                    class="admin-chip-button">+ افزودن ویژگی</button>
        </div>
        <p class="mb-4 text-xs text-neutral-500">
            حداکثر {{ \App\Models\ProductHighlight::MAX_PER_PRODUCT }} مورد — مثلاً «الیاف مقاوم با طول عمر بالا». تصویر هر ویژگی (مثلاً نمای نزدیک) به شکل دایره نمایش داده می‌شود.
        </p>

        <template x-if="highlights.length === 0">
            <p class="text-sm text-neutral-500">ویژگی‌ای ثبت نشده است.</p>
        </template>

        <template x-for="(highlight, index) in highlights" :key="index">
            <div class="mb-3 flex flex-wrap items-center gap-3 rounded-lg border border-neutral-200 bg-neutral-50/60 p-3">
                <input type="hidden" :name="`highlights[${index}][id]`" :value="highlight.id ?? ''">
                <template x-if="highlight.image_url">
                    <img :src="highlight.image_url" alt="" class="h-14 w-14 rounded-full border border-neutral-200 object-cover">
                </template>
                <div class="min-w-48 flex-1">
                    <label class="mb-1 block text-xs text-neutral-500">عنوان</label>
                    <input :name="`highlights[${index}][title]`" x-model="highlight.title" required maxlength="120"
                           class="w-full admin-input admin-input-sm">
                </div>
                <div>
                    <label class="mb-1 block text-xs text-neutral-500" x-text="highlight.image_url ? 'تعویض تصویر' : 'تصویر (اختیاری)'"></label>
                    <input type="file" :name="`highlights[${index}][image]`" accept="image/jpeg,image/png,image/webp" class="text-xs">
                </div>
                <label x-show="highlight.image_url" class="flex items-center gap-1 text-xs text-red-700">
                    <input type="checkbox" :name="`highlights[${index}][remove_image]`" value="1"> حذف تصویر
                </label>
                <button type="button" @click="highlights.splice(index, 1)" class="text-xs text-red-600 hover:text-red-800">حذف</button>
            </div>
        </template>
    </div>

    <x-admin.seo-card :meta-title="old('meta_title', $product->meta_title)" :meta-description="old('meta_description', $product->meta_description)"
                      :url-prefix="url('products').'/'" :slug="$product->slug" fallback-title-field="name" />
    </div>

    {{-- Sidebar --}}
    <div class="space-y-6">
        <div class="admin-card">
            <div class="admin-card-header"><h2 class="admin-card-title">انتشار و فروش</h2></div>
            <div class="admin-card-body">
                <label class="flex items-start gap-2 text-sm">
                    <input type="checkbox" name="is_active" value="1" class="admin-check mt-0.5" @checked(old('is_active', $product->is_active ?? false))>
                    <span><span class="font-medium">فعال</span> — در سایت نمایش داده شود</span>
                </label>
                <label class="flex items-start gap-2 text-sm">
                    <input type="checkbox" name="is_featured" value="1" class="admin-check mt-0.5" @checked(old('is_featured', $product->is_featured ?? false))>
                    <span><span class="font-medium">محصول ویژه</span> — در صفحه اصلی</span>
                </label>

                <div>
                    <p class="admin-label">نوع فروش</p>
                    <div class="grid gap-2">
                        <label class="flex cursor-pointer items-start gap-2 rounded-lg border p-3 text-sm transition"
                               :class="type === 'quote' ? 'border-brand-accent bg-orange-50/50' : 'border-neutral-200'">
                            <input type="radio" name="type" value="quote" x-model="type" class="mt-1 accent-brand-accent-dark">
                            <span><span class="font-medium">استعلامی</span><br><span class="text-xs text-neutral-500">بدون قیمت — مشتری درخواست قیمت می‌دهد</span></span>
                        </label>
                        <label class="flex cursor-pointer items-start gap-2 rounded-lg border p-3 text-sm transition"
                               :class="type === 'priced' ? 'border-brand-accent bg-orange-50/50' : 'border-neutral-200'">
                            <input type="radio" name="type" value="priced" x-model="type" class="mt-1 accent-brand-accent-dark">
                            <span><span class="font-medium">قیمت‌دار</span><br><span class="text-xs text-neutral-500">با تنوع (ابعاد) و قیمت مشخص</span></span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="field-stock_status" class="admin-label">موجودی</label>
                        <select id="field-stock_status" name="stock_status" class="admin-input">
                            <option value="">— نامشخص —</option>
                            <option value="in_stock" @selected(old('stock_status', $product->stock_status) === 'in_stock')>موجود</option>
                            <option value="out_of_stock" @selected(old('stock_status', $product->stock_status) === 'out_of_stock')>ناموجود</option>
                            <option value="made_to_order" @selected(old('stock_status', $product->stock_status) === 'made_to_order')>تولید سفارشی</option>
                        </select>
                    </div>
                    <div>
                        <label for="field-sort_order" class="admin-label">ترتیب نمایش</label>
                        <input id="field-sort_order" type="number" name="sort_order" value="{{ old('sort_order', $product->sort_order ?? 0) }}" class="admin-input" dir="ltr">
                    </div>
                </div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-header"><h2 class="admin-card-title">تصاویر محصول (گالری)</h2></div>
            <div class="p-5">
                <x-admin.media-picker name="image_ids[]" multiple :items="$galleryItems" empty-text="هنوز تصویری برای محصول انتخاب نشده است." />
                <p class="admin-hint">پیشنهاد: مربعی، حداقل ۸۰۰×۸۰۰ پیکسل، پس‌زمینه‌ی روشن.</p>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-header"><h2 class="admin-card-title">دسته‌بندی</h2></div>
            <div class="admin-card-body">
                <div>
                    <label for="field-primary_category_id" class="admin-label">دسته اصلی</label>
                    <select id="field-primary_category_id" name="primary_category_id" class="admin-input">
                        <option value="">— انتخاب کنید —</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((int) old('primary_category_id', $product->primary_category_id) === $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-data="{ filter: '' }">
                    <p class="admin-label">دسته‌های دیگر</p>
                    @if ($categories->count() > 8)
                        <input type="search" x-model="filter" placeholder="جستجوی دسته…" class="admin-input admin-input-sm mb-2">
                    @endif
                    <div class="max-h-56 space-y-1 overflow-y-auto rounded-lg border border-neutral-200 p-2">
                        @foreach ($categories as $category)
                            <label class="flex items-center gap-2 rounded px-2 py-1 text-sm hover:bg-neutral-50"
                                   x-show="filter === '' || {{ \Illuminate\Support\Js::from($category->name) }}.includes(filter)">
                                <input type="checkbox" name="category_ids[]" value="{{ $category->id }}" class="admin-check"
                                       @checked(in_array($category->id, old('category_ids', $selectedCategoryIds)))>
                                {{ $category->name }}
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

    <div class="admin-card p-5">
        <h2 class="admin-card-title mb-1">کاربردها</h2>
        <p class="mb-4 text-xs text-neutral-500">
            کاربردهای این محصول را انتخاب کنید. برای افزودن یا ویرایش کاربردها و آیکون‌هایشان به
            <a href="{{ route('admin.applications.index') }}" class="underline" target="_blank">صفحه‌ی کاربردها</a> بروید.
        </p>
        @if ($applications->isEmpty())
            <p class="text-sm text-neutral-500">هنوز کاربردی تعریف نشده است.</p>
        @else
            <div class="flex flex-wrap gap-2">
                @foreach ($applications as $application)
                    <label class="cursor-pointer">
                        <input type="checkbox" name="application_ids[]" value="{{ $application->id }}" class="peer sr-only"
                               @checked(in_array($application->id, old('application_ids', $selectedApplicationIds)))>
                        <span class="flex items-center gap-2 rounded-lg border border-neutral-300 px-3 py-2 text-sm peer-checked:border-neutral-900 peer-checked:bg-neutral-900 peer-checked:text-white peer-focus-visible:ring-2">
                            @if ($application->icon)
                                <img src="{{ $application->icon->url() }}" alt="" class="h-5 w-5 object-contain">
                            @endif
                            {{ $application->name }}
                        </span>
                    </label>
                @endforeach
            </div>
        @endif
    </div>
    </div>
    </div>

    <x-admin.save-bar :cancel="route('admin.products.index')"
                      :view="$product->exists && $product->is_active ? route('products.show', $product) : null"
                      :label="$product->exists ? 'ذخیره تغییرات' : 'ایجاد محصول'" />
</form>
@endsection
