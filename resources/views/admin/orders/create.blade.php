@extends('admin.layout')

@section('title', 'ثبت سفارش دستی')

@php
    use App\Http\Requests\Admin\StoreManualOrderRequest;
    use App\Models\Order;
    use App\Services\Checkout\ShippingMethod;

    $catalog = $products->mapWithKeys(fn ($product) => [$product->id => [
        'name' => $product->name,
        'schematic' => $product->schematics->first()?->url(),
        'variants' => $product->variants->map(fn ($variant) => [
            'id' => $variant->id,
            'label' => $variant->label,
            'price_toman' => intdiv($variant->price_rial, 10),
        ])->values(),
        'fields' => $product->dimensionFields->map(fn ($field) => [
            'id' => $field->id,
            'type' => $field->type,
            'options' => $field->options ?? [],
            'code' => $field->code,
            'label' => $field->label,
            'unit' => $field->unitLabel(),
            'required' => $field->is_required,
            'min' => $field->min_value,
            'max' => $field->max_value,
        ])->values(),
    ]]);

    $initialItems = old('items', $prefill['items']);
@endphp

@section('content')
<div class="mb-6 flex flex-wrap items-baseline justify-between gap-2">
    <h1 class="text-lg font-semibold">ثبت سفارش دستی</h1>
    <a href="{{ route('admin.orders.index') }}" class="text-sm text-neutral-500 hover:text-neutral-900">بازگشت به سفارش‌ها</a>
</div>

@if ($quoteRequest)
    <div class="mb-6 rounded border border-blue-200 bg-blue-50 p-3 text-sm text-blue-800">
        از درخواست استعلام #{{ $quoteRequest->id }} ({{ $quoteRequest->name }}{{ $quoteRequest->product ? ' — '.$quoteRequest->product->name : '' }}).
        اطلاعات مشتری و ابعاد پر شده‌اند — قیمت را وارد و بقیه را بررسی کنید. پس از ثبت، درخواست به این سفارش متصل و «بسته» می‌شود.
        @if ($quoteRequest->message)
            <p class="mt-2 whitespace-pre-line text-xs text-blue-700">پیام مشتری: {{ $quoteRequest->message }}</p>
        @endif
    </div>
@endif

<x-admin.form-errors />

<form method="POST" action="{{ route('admin.orders.store') }}" class="max-w-4xl space-y-6"
      x-data="manualOrderForm(@js($catalog), @js(array_values($initialItems)))">
    @csrf
    @if ($quoteRequest)
        <input type="hidden" name="quote_request_id" value="{{ $quoteRequest->id }}">
    @endif

    <div class="admin-card p-6">
        <h2 class="mb-4 text-sm font-medium text-neutral-500">مشتری</h2>
        <div class="grid gap-3 sm:grid-cols-3">
            <div>
                <label for="name" class="admin-label">نام و نام خانوادگی / شرکت</label>
                <input id="name" name="name" value="{{ old('name', $prefill['name']) }}" required
                       class="admin-input">
            </div>
            <div>
                <label for="phone" class="admin-label">شماره موبایل</label>
                <input id="phone" name="phone" type="tel" value="{{ old('phone', $prefill['phone']) }}" required dir="ltr"
                       class="admin-input">
            </div>
            <div>
                <label for="email" class="admin-label">ایمیل (اختیاری)</label>
                <input id="email" name="email" type="email" value="{{ old('email', $prefill['email']) }}" dir="ltr"
                       class="admin-input">
            </div>
        </div>
        <p class="mt-2 text-xs text-neutral-500">
            اگر مشتری‌ای با این شماره از قبل وجود داشته باشد، سفارش به همان مشتری متصل می‌شود (اطلاعات پرونده‌اش تغییر نمی‌کند).
        </p>
    </div>

    <div class="admin-card p-6">
        <h2 class="mb-4 text-sm font-medium text-neutral-500">آدرس ارسال</h2>
        <div class="grid gap-3 sm:grid-cols-3">
            <input name="province" placeholder="استان" value="{{ old('province') }}" required aria-label="استان"
                   class="admin-input">
            <input name="city" placeholder="شهر" value="{{ old('city') }}" required aria-label="شهر"
                   class="admin-input">
            <input name="postal_code" placeholder="کد پستی (اختیاری)" value="{{ old('postal_code') }}" dir="ltr" aria-label="کد پستی"
                   class="admin-input">
        </div>
        <textarea name="address_line" rows="2" placeholder="آدرس کامل" required aria-label="آدرس کامل"
                  class="mt-3 admin-input">{{ old('address_line') }}</textarea>
    </div>

    <div class="admin-card p-6">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-sm font-medium text-neutral-500">اقلام سفارش</h2>
            <button type="button" @click="addItem()" class="rounded border border-neutral-300 px-3 py-1 text-xs hover:bg-neutral-50">
                + افزودن ردیف
            </button>
        </div>

        <div class="space-y-4">
            <template x-for="(item, index) in items" :key="item.key">
                <div class="space-y-3 rounded border border-neutral-200 p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-neutral-500" x-text="`ردیف ${index + 1}`"></span>
                        <button type="button" x-show="items.length > 1" @click="removeItem(index)" class="text-xs text-red-600 hover:text-red-800">حذف ردیف</button>
                    </div>

                    <div class="flex flex-wrap items-end gap-3">
                        <div class="min-w-56 flex-1">
                            <label class="mb-1 block text-xs text-neutral-500">محصول</label>
                            <select :name="`items[${index}][product_id]`" x-model="item.product_id" @change="productChanged(item)" required
                                    class="admin-input admin-input-sm">
                                <option value="">— انتخاب محصول —</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}">
                                        {{ $product->name }}{{ $product->type === 'quote' ? ' (استعلامی)' : '' }}{{ $product->is_active ? '' : ' (غیرفعال)' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="w-48" x-show="variantsOf(item).length > 0">
                            <label class="mb-1 block text-xs text-neutral-500">تنوع (اختیاری)</label>
                            <select :name="`items[${index}][product_variant_id]`" x-model="item.product_variant_id" @change="variantChanged(item)"
                                    class="admin-input admin-input-sm">
                                <option value="">— بدون تنوع —</option>
                                <template x-for="variant in variantsOf(item)" :key="variant.id">
                                    <option :value="variant.id" x-text="`${variant.label} — ${toman(variant.price_toman)}`"
                                            :selected="String(variant.id) === String(item.product_variant_id)"></option>
                                </template>
                            </select>
                        </div>
                        <div class="w-24">
                            <label class="mb-1 block text-xs text-neutral-500">تعداد</label>
                            <input type="number" min="1" :name="`items[${index}][quantity]`" x-model="item.quantity" required dir="ltr"
                                   class="admin-input admin-input-sm">
                        </div>
                        <div class="w-36">
                            <label class="mb-1 block text-xs text-neutral-500">قیمت واحد (تومان)</label>
                            <input type="number" min="0" :name="`items[${index}][unit_price_toman]`" x-model="item.unit_price_toman" required dir="ltr"
                                   class="admin-input admin-input-sm">
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs text-neutral-500">عنوان ردیف در فاکتور (اختیاری — خالی بماند تا نام محصول استفاده شود)</label>
                        <input :name="`items[${index}][name]`" x-model="item.name"
                               class="admin-input admin-input-sm">
                    </div>

                    <template x-if="fieldsOf(item).length > 0">
                        <div class="rounded border border-neutral-100 bg-neutral-50 p-3">
                            <div class="mb-3 flex items-center justify-between">
                                <p class="text-xs font-medium">ابعاد</p>
                                <a x-show="productOf(item)?.schematic" :href="productOf(item)?.schematic" target="_blank"
                                   class="text-xs text-blue-700 hover:underline">مشاهده شماتیک</a>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-3">
                                <template x-for="field in fieldsOf(item)" :key="field.id">
                                    <div>
                                        <label class="mb-1 block text-xs">
                                            <span class="rounded bg-neutral-900 px-1 font-bold text-white" dir="ltr" x-text="field.code"></span>
                                            <span x-text="field.label"></span>
                                            <span x-show="field.required" class="text-red-600">*</span>
                                        </label>
                                        <div class="flex overflow-hidden rounded border border-neutral-300 bg-white">
                                            <template x-if="field.type === 'choice'">
                                                <select :name="`items[${index}][dimensions][${field.id}]`" x-model="item.dimensions[field.id]" :required="field.required"
                                                        class="w-full min-w-0 px-2 py-1.5 text-sm outline-none">
                                                    <option value="">— انتخاب —</option>
                                                    <template x-for="option in field.options" :key="option">
                                                        <option :value="option" x-text="option" :selected="item.dimensions[field.id] === option"></option>
                                                    </template>
                                                </select>
                                            </template>
                                            <template x-if="field.type !== 'choice'">
                                                <input :name="`items[${index}][dimensions][${field.id}]`" x-model="item.dimensions[field.id]"
                                                       :required="field.required" inputmode="decimal" dir="ltr"
                                                       class="w-full min-w-0 px-2 py-1.5 text-sm outline-none">
                                            </template>
                                            <span class="flex items-center bg-neutral-50 px-2 text-xs text-neutral-500" x-text="field.unit"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <textarea :name="`items[${index}][customization_notes]`" x-model="item.customization_notes" rows="2"
                                      placeholder="توضیحات ساخت (اختیاری)" aria-label="توضیحات ساخت"
                                      class="mt-3 admin-input admin-input-sm"></textarea>
                        </div>
                    </template>

                    <p class="text-left text-sm" x-text="`جمع ردیف: ${toman(lineTotal(item))}`"></p>
                </div>
            </template>
        </div>

        <p class="mt-4 border-t border-neutral-100 pt-3 text-left text-sm font-bold" x-text="`جمع کالاها: ${toman(subtotal())}`"></p>
    </div>

    <div class="admin-card p-6" x-data="{ shippingMethod: @js(old('shipping_method', $defaultShippingMethod->value)) }">
        <h2 class="mb-4 text-sm font-medium text-neutral-500">ارسال، وضعیت و پرداخت</h2>
        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label for="shipping_method" class="admin-label">روش ارسال</label>
                <select id="shipping_method" name="shipping_method" x-model="shippingMethod" class="admin-input">
                    @foreach (ShippingMethod::cases() as $case)
                        <option value="{{ $case->value }}">{{ $case->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div x-show="shippingMethod !== @js(ShippingMethod::CashOnDelivery->value)">
                <label for="shipping_cost_toman" class="admin-label">هزینه ارسال (تومان)</label>
                <input id="shipping_cost_toman" name="shipping_cost_toman" type="number" min="0" dir="ltr"
                       value="{{ old('shipping_cost_toman') }}" placeholder="خالی = هنوز مشخص نیست"
                       class="admin-input">
            </div>
            <div>
                <label for="status" class="admin-label">وضعیت سفارش</label>
                <select id="status" name="status" class="admin-input">
                    @foreach (StoreManualOrderRequest::INITIAL_STATUSES as $value)
                        <option value="{{ $value }}" @selected(old('status', 'confirmed') === $value)>{{ Order::STATUS_LABELS[$value] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="payment_status" class="admin-label">وضعیت پرداخت</label>
                <select id="payment_status" name="payment_status" class="admin-input">
                    @foreach (Order::PAYMENT_STATUS_LABELS as $value => $label)
                        <option value="{{ $value }}" @selected(old('payment_status', 'unpaid') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mt-3">
            <label for="notes" class="admin-label">یادداشت داخلی</label>
            <textarea id="notes" name="notes" rows="2" class="admin-input">{{ old('notes', $prefill['notes']) }}</textarea>
        </div>
        <label class="mt-4 flex items-center gap-2 text-sm">
            <input type="hidden" name="notify_customer" value="0">
            <input type="checkbox" name="notify_customer" value="1" @checked(old('notify_customer', '1') === '1')>
            ارسال پیامک و ایمیل تأیید سفارش به مشتری (با شماره سفارش، برای پیگیری)
        </label>
    </div>

    <button type="submit" class="rounded bg-neutral-900 px-5 py-2.5 text-sm text-white hover:bg-neutral-700">
        ثبت سفارش
    </button>
</form>
@endsection
