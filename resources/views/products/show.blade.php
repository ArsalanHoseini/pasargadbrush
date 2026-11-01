@extends('layouts.public')

@section('title', $product->meta_title ?? $product->name)
@section('meta_description', $product->meta_description ?? \Illuminate\Support\Str::limit(strip_tags($product->short_description ?: ($product->description ?? '')), 150))
@section('canonical', url()->current())

@push('structured-data')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Product",
    "name": {!! json_encode($product->name, JSON_UNESCAPED_UNICODE) !!},
    "sku": {!! json_encode($product->sku) !!},
    @if ($product->images->isNotEmpty())
    "image": {!! json_encode($product->images->first()->url()) !!},
    @endif
    "description": {!! json_encode(strip_tags($product->description ?? ''), JSON_UNESCAPED_UNICODE) !!}
    @if ($product->type === 'priced' && $product->variants->isNotEmpty())
    ,"offers": {
        "@@type": "AggregateOffer",
        "priceCurrency": "IRR",
        "lowPrice": {{ $product->variants->min('price_rial') }},
        "highPrice": {{ $product->variants->max('price_rial') }}
    }
    @endif
}
</script>
@endpush

@php
    $isPriced = $product->type === 'priced';
    $accountCustomer = auth('customer')->user();
    // Which button the card opens in: back to the quote form after a failed or sent quote request.
    $startInQuoteMode = old('order_mode') === 'quote'
        || $errors->hasAny(['name', 'phone', 'message', 'email', 'company'])
        || session('quote_request_sent');
    $selectedVariantId = (string) old('product_variant_id', $orderableVariants->firstWhere('is_default', true)?->id ?? $orderableVariants->first()?->id ?? '');
    $variantPrices = $product->variants->mapWithKeys(fn ($variant) => [(string) $variant->id => \App\Support\Money::toman($variant->price_rial)]);
    $canAddToCart = $isPriced ? $orderableVariants->isNotEmpty() : $quoteOrderUnavailableReason === null;
@endphp

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
    <nav class="mb-6 text-xs text-neutral-500" aria-label="مسیر صفحه">
        <a href="{{ route('home') }}" class="hover:text-neutral-800">خانه</a>
        @if ($product->primaryCategory)
            <span class="mx-1">/</span>
            <a href="{{ route('categories.show', $product->primaryCategory) }}" class="hover:text-neutral-800">{{ $product->primaryCategory->name }}</a>
        @endif
        <span class="mx-1">/</span>
        <span class="text-neutral-700">{{ $product->name }}</span>
    </nav>

    <div class="grid gap-8 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)]">
        {{-- Order column (right in RTL) --}}
        <div class="lg:order-none">
            <h1 class="text-2xl font-bold text-neutral-900 sm:text-3xl">{{ $product->name }}</h1>
            <span class="mt-3 block h-1 w-16 rounded-full bg-brand-accent" aria-hidden="true"></span>
            @if ($product->short_description)
                <p class="mt-4 text-sm leading-7 text-neutral-600">{{ $product->short_description }}</p>
            @endif

            <form method="POST" action="{{ route('cart.items.store') }}"
                  class="mt-6"
                  x-data="{
                      mode: @js($startInQuoteMode ? 'quote' : 'cart'),
                      variantId: @js($selectedVariantId),
                      prices: @js($variantPrices),
                  }">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="order_mode" :value="mode">

                <div class="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm">
                    <div class="flex items-center gap-2 border-b border-neutral-200 bg-neutral-50 px-5 py-3">
                        <svg class="h-5 w-5 text-brand-charcoal" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <path d="M10.3 4.3c.4-1.7 3-1.7 3.4 0a1.7 1.7 0 002.6 1.1c1.5-.9 3.3.8 2.4 2.4a1.7 1.7 0 001 2.5c1.8.5 1.8 3 0 3.4a1.7 1.7 0 00-1 2.6c.9 1.5-.9 3.3-2.4 2.4a1.7 1.7 0 00-2.6 1c-.4 1.8-3 1.8-3.4 0a1.7 1.7 0 00-2.5-1c-1.6.9-3.3-.9-2.4-2.4a1.7 1.7 0 00-1.1-2.6c-1.7-.4-1.7-2.9 0-3.4a1.7 1.7 0 001.1-2.5c-.9-1.6.8-3.3 2.4-2.4a1.7 1.7 0 002.5-1.1z" stroke-linejoin="round"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        <h2 class="text-base font-bold text-neutral-900">مشخصات و سفارش</h2>
                    </div>

                    <div class="space-y-4 p-5">
                        @if (session('status'))
                            <div class="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700">{{ session('status') }}</div>
                        @endif
                        @if (session('quote_request_sent'))
                            <div class="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700">
                                درخواست شما ثبت شد. کارشناسان ما در اسرع وقت با شما تماس می‌گیرند.
                            </div>
                        @endif
                        @if ($errors->hasAny(['quantity', 'product_variant_id', 'website']))
                            <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                                {{ $errors->first('quantity') ?: ($errors->first('product_variant_id') ?: $errors->first('website')) }}
                            </div>
                        @elseif ($errors->any())
                            <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">لطفاً موارد مشخص‌شده را اصلاح کنید.</div>
                        @endif

                        @if ($isPriced && $product->variants->count() > 1)
                            <fieldset>
                                <legend class="mb-2 text-sm font-medium text-neutral-800">نوع / اندازه</legend>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($product->variants as $variant)
                                        @php $variantAvailable = $orderableVariants->contains('id', $variant->id); @endphp
                                        <label @class(['cursor-pointer' => $variantAvailable, 'cursor-not-allowed opacity-50' => ! $variantAvailable])>
                                            <input type="radio" name="product_variant_id" value="{{ $variant->id }}" x-model="variantId"
                                                   class="peer sr-only" @disabled(! $variantAvailable)>
                                            <span class="block rounded-lg border border-neutral-300 px-4 py-2 text-sm transition peer-checked:border-brand-charcoal peer-checked:bg-brand-charcoal peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-brand-accent">
                                                {{ $variant->label }}
                                                @unless ($variantAvailable)<span class="text-xs">(ناموجود)</span>@endunless
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @elseif ($isPriced && $orderableVariants->isNotEmpty())
                            <input type="hidden" name="product_variant_id" value="{{ $orderableVariants->first()->id }}">
                        @endif

                        @if ($product->requiresDimensions())
                            <x-dimension-fields :product="$product" />
                        @endif

                        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-neutral-100 pt-4">
                            <div class="flex items-center gap-3">
                                <label for="quantity" class="text-sm font-medium text-neutral-800">تعداد</label>
                                <input id="quantity" name="quantity" type="number" min="1" max="{{ \App\Services\Cart\CartManager::MAX_QUANTITY }}"
                                       value="{{ old('quantity', 1) }}" dir="ltr"
                                       class="w-20 rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                            </div>
                            <div class="text-left text-sm">
                                @if ($isPriced && $product->variants->isNotEmpty())
                                    <p class="text-xs text-neutral-500">{{ $product->requiresDimensions() ? 'قیمت پایه' : 'قیمت' }}</p>
                                    <p class="text-lg font-bold text-neutral-900" x-text="prices[variantId] ?? ''">{{ $variantPrices[$selectedVariantId] ?? '' }}</p>
                                @else
                                    <p class="text-sm font-medium text-neutral-700">قیمت پس از بررسی کارشناس اعلام می‌شود</p>
                                @endif
                            </div>
                        </div>

                        @if ($product->requiresDimensions() && $isPriced)
                            <p class="text-xs text-neutral-500">قیمت نهایی پس از بررسی مشخصات واردشده توسط کارشناس تأیید و به شما اعلام می‌شود.</p>
                        @elseif (! $isPriced)
                            <p class="text-xs text-neutral-500">این محصول بر اساس سفارش شما ساخته می‌شود؛ سفارش را ثبت کنید تا کارشناس قیمت را بررسی و اعلام کند، یا برای مشاوره استعلام قیمت بفرستید.</p>
                        @endif

                        {{-- Contact details, only for the "مشاوره و استعلام قیمت" button --}}
                        <div x-show="mode === 'quote'" x-cloak class="space-y-3 rounded-lg bg-neutral-50 p-4">
                            <p class="text-sm font-medium text-neutral-800">اطلاعات تماس برای مشاوره و استعلام قیمت</p>
                            <div class="absolute h-0 w-0 overflow-hidden opacity-0" aria-hidden="true">
                                <label>وب‌سایت<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label for="quote-name" class="sr-only">نام و نام خانوادگی</label>
                                    <input id="quote-name" name="name" placeholder="نام و نام خانوادگی *" value="{{ old('name', $accountCustomer?->name) }}"
                                           :required="mode === 'quote'" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                                    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="quote-phone" class="sr-only">شماره تماس</label>
                                    <input id="quote-phone" name="phone" type="tel" placeholder="شماره تماس *" value="{{ old('phone', $accountCustomer?->phone) }}" dir="ltr"
                                           :required="mode === 'quote'" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                                    @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="quote-company" class="sr-only">نام شرکت</label>
                                    <input id="quote-company" name="company" placeholder="نام شرکت (اختیاری)" value="{{ old('company', $accountCustomer?->company_name) }}"
                                           class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label for="quote-email" class="sr-only">ایمیل</label>
                                    <input id="quote-email" name="email" type="email" placeholder="ایمیل (اختیاری)" value="{{ old('email', $accountCustomer?->email) }}" dir="ltr"
                                           class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                                </div>
                            </div>
                            <div>
                                <label for="quote-message" class="sr-only">توضیح نیاز شما</label>
                                <textarea id="quote-message" name="message" rows="3" placeholder="توضیح نیاز شما (کاربرد، تیراژ، سؤال‌ها) *"
                                          :required="mode === 'quote'" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">{{ old('message') }}</textarea>
                                @error('message')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex flex-col-reverse gap-3 sm:flex-row">
                    {{-- Opens the contact details first; a second press sends the quote request. --}}
                    <button type="button" x-show="mode !== 'quote'" @click="mode = 'quote'; $nextTick(() => document.getElementById('quote-name')?.focus())"
                            class="flex flex-1 items-center justify-center gap-2 rounded-xl border border-neutral-300 bg-white px-5 py-3.5 text-sm font-semibold text-neutral-800 hover:border-neutral-500">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 14v-2a9 9 0 0118 0v2M3 14a2 2 0 002 2h1v-5H5a2 2 0 00-2 2zm18 0a2 2 0 01-2 2h-1v-5h1a2 2 0 012 2zM18 16v1a3 3 0 01-3 3h-2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        مشاوره و استعلام قیمت
                    </button>
                    <button type="submit" x-show="mode === 'quote'" x-cloak formaction="{{ route('quote-requests.store') }}"
                            class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-brand-charcoal px-5 py-3.5 text-sm font-semibold text-white hover:bg-neutral-800">
                        ارسال درخواست استعلام قیمت
                    </button>

                    @if ($canAddToCart)
                        <button type="submit" @click="mode = 'cart'"
                                class="flex flex-[1.3] items-center justify-center gap-2 rounded-xl bg-brand-accent px-5 py-3.5 text-sm font-bold text-white shadow-sm hover:bg-brand-accent-dark">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 3h2l.4 2M7 13h10l3-8H5.4M7 13L5.4 5M7 13l-1.5 7h11.5M9 20a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            افزودن به سبد و ثبت سفارش
                        </button>
                    @else
                        <p class="flex flex-[1.3] items-center justify-center rounded-xl bg-neutral-100 px-5 py-3.5 text-sm font-semibold text-neutral-500">
                            {{ $quoteOrderUnavailableReason ?? 'این محصول در حال حاضر ناموجود است.' }}
                        </p>
                    @endif
                </div>
                <button type="button" x-show="mode === 'quote'" x-cloak @click="mode = 'cart'" class="mt-2 text-xs text-neutral-500 hover:text-neutral-800">
                    انصراف از استعلام
                </button>
            </form>
        </div>

        {{-- Media column (left in RTL): gallery, then the schematics --}}
        <div class="space-y-6">
            @if ($product->images->isNotEmpty())
                <div x-data="{ active: 0 }">
                    <div class="flex aspect-[4/3] items-center justify-center overflow-hidden rounded-2xl bg-neutral-50">
                        @foreach ($product->images as $image)
                            <img src="{{ $image->url() }}" alt="{{ $product->name }}" x-show="active === {{ $loop->index }}"
                                 @if (! $loop->first) x-cloak loading="lazy" @endif
                                 class="h-full w-full object-contain">
                        @endforeach
                    </div>
                    @if ($product->images->count() > 1)
                        <div class="mt-3 flex gap-2 overflow-x-auto">
                            @foreach ($product->images as $image)
                                <button type="button" @click="active = {{ $loop->index }}"
                                        :class="active === {{ $loop->index }} ? 'border-brand-accent' : 'border-transparent'"
                                        class="h-16 w-16 shrink-0 overflow-hidden rounded-lg border-2 bg-neutral-50"
                                        aria-label="تصویر {{ $loop->iteration }}">
                                    <img src="{{ $image->url() }}" alt="" class="h-full w-full object-cover" loading="lazy">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            @else
                <div class="flex aspect-[4/3] items-center justify-center rounded-2xl bg-neutral-100 text-sm text-neutral-400">بدون تصویر</div>
            @endif

            @if ($product->schematics->isNotEmpty())
                <section aria-labelledby="schematics-heading" class="rounded-2xl border border-neutral-200 bg-white p-4">
                    <h2 id="schematics-heading" class="mb-3 inline-flex items-center gap-2 rounded-lg bg-brand-charcoal px-3 py-1.5 text-xs font-semibold text-white">
                        نمای شماتیک (بدون مقیاس)
                    </h2>
                    <div @class([
                        'grid gap-4',
                        'sm:grid-cols-2' => $product->schematics->count() === 2 || $product->schematics->count() === 4,
                        'sm:grid-cols-3' => $product->schematics->count() === 3 || $product->schematics->count() > 4,
                    ])>
                        @foreach ($product->schematics as $schematic)
                            <a href="{{ $schematic->url() }}" target="_blank" rel="noopener"
                               class="flex items-center justify-center rounded-xl border border-neutral-100 bg-neutral-50 p-3 hover:border-neutral-300">
                                <img src="{{ $schematic->url() }}" alt="شماتیک {{ $loop->iteration }} — {{ $product->name }}" class="max-h-64 w-auto object-contain" loading="lazy">
                            </a>
                        @endforeach
                    </div>
                    @if ($product->requiresDimensions())
                        <p class="mt-3 text-xs text-neutral-500">حروف روی شکل‌ها همان حروف کنار فیلدهای فرم سفارش هستند.</p>
                    @endif
                </section>
            @endif
        </div>
    </div>

    @if ($product->description || ! empty($product->specifications))
        <div class="mt-12 grid gap-8 lg:grid-cols-[minmax(0,7fr)_minmax(0,5fr)]">
            @if ($product->description)
                <section aria-labelledby="description-heading">
                    <h2 id="description-heading" class="mb-4 text-lg font-bold text-neutral-900">توضیحات</h2>
                    <div class="article-content text-sm leading-7 text-neutral-700">{!! $product->description !!}</div>
                </section>
            @endif
            @if (! empty($product->specifications))
                <section aria-labelledby="specs-heading">
                    <h2 id="specs-heading" class="mb-4 text-lg font-bold text-neutral-900">مشخصات فنی</h2>
                    <dl class="divide-y divide-neutral-100 rounded-xl border border-neutral-200 bg-white px-4 text-sm">
                        @foreach ($product->specifications as $key => $value)
                            <div class="flex justify-between gap-4 py-3">
                                <dt class="text-neutral-500">{{ $key }}</dt>
                                <dd class="font-medium text-neutral-900">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>
            @endif
        </div>
    @endif

    @if ($related->isNotEmpty())
        <div class="mt-16">
            <h2 class="mb-6 text-lg font-bold text-neutral-900">محصولات مرتبط</h2>
            <div class="grid grid-cols-2 gap-5 sm:grid-cols-4">
                @foreach ($related as $item)
                    <a href="{{ route('products.show', $item) }}" class="group rounded-lg border border-neutral-200 bg-white p-3 transition hover:shadow-md">
                        <div class="mb-3 aspect-square overflow-hidden rounded bg-neutral-100">
                            @if ($item->images->isNotEmpty())
                                <img src="{{ $item->images->first()->url() }}" alt="{{ $item->name }}" class="h-full w-full object-cover" loading="lazy">
                            @endif
                        </div>
                        <h3 class="text-sm font-medium text-neutral-900 group-hover:text-brand-accent-dark">{{ $item->name }}</h3>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
