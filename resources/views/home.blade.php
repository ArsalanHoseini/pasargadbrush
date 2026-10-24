@extends('layouts.public')

@section('title', 'تولیدکننده برس و فرچه صنعتی')
@section('meta_description', 'تولیدکننده انواع برس و فرچه صنعتی: برس ابزاری، قالیشویی و نظافتی، هویج‌شویی و سورتینگ، نواری و درزگیر، صنایع غذایی. ساخت سفارشی بر اساس نیاز شما.')

@push('structured-data')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Organization",
    "name": {!! json_encode(setting('company_legal_name'), JSON_UNESCAPED_UNICODE) !!},
    "url": {!! json_encode(url('/')) !!},
    "logo": {!! json_encode(asset('images/logo.png')) !!},
    "email": {!! json_encode(setting('contact_email')) !!},
    "telephone": {!! json_encode(setting('contact_mobile')) !!},
    "address": {
        "@@type": "PostalAddress",
        "streetAddress": {!! json_encode(setting('contact_address'), JSON_UNESCAPED_UNICODE) !!}
    }
}
</script>
@endpush

@php
$mosaicProducts = $products->filter(fn ($product) => $product->images->isNotEmpty())->take(4)->values();
@endphp

@section('content')
{{-- Hero --}}
<section class="relative overflow-hidden bg-brand-charcoal-deep">
    <div class="bg-grid-industrial absolute inset-0 opacity-40"></div>
    <div class="absolute inset-0 bg-gradient-to-t from-brand-charcoal-deep via-transparent to-transparent"></div>

    <div class="relative mx-auto grid max-w-6xl items-center gap-12 px-4 py-20 sm:px-6 lg:grid-cols-2 lg:py-28">
        <div data-animate class="text-white">
            <span class="mb-4 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-4 py-1.5 text-xs font-medium tracking-wide text-neutral-300">
                تولیدکننده صنعتی از سال ۱۳۷۴
            </span>
            <h1 class="mb-5 text-4xl font-bold leading-[1.15] sm:text-5xl">
                تولیدکننده انواع
                <span class="text-brand-accent">برس و فرچه صنعتی</span>
            </h1>
            <p class="mb-9 max-w-md leading-7 text-neutral-400">
                از برس‌های ابزاری و قالیشویی تا نمونه‌های سفارشی برای صنایع غذایی، نفت و پتروشیمی، نساجی و بیش از ۴۰ کاربرد دیگر — ساخته‌شده بر اساس نیاز دقیق شما.
            </p>
            <div class="flex flex-wrap gap-3">
                <a href="#categories" class="rounded-full bg-brand-accent px-7 py-3.5 text-sm font-semibold text-white transition hover:bg-brand-accent-dark">
                    مشاهده محصولات
                </a>
                <a href="#contact" class="rounded-full border border-white/20 px-7 py-3.5 text-sm font-semibold text-white transition hover:border-white/40 hover:bg-white/5">
                    درخواست قیمت و مشاوره
                </a>
            </div>
        </div>

        @if ($mosaicProducts->isNotEmpty())
            <div data-animate class="relative mx-auto grid max-w-sm grid-cols-2 gap-4 sm:max-w-md">
                @foreach ($mosaicProducts as $index => $product)
                    <a href="{{ route('products.show', $product) }}"
                       class="group overflow-hidden rounded-xl bg-neutral-800 shadow-2xl shadow-black/40 transition hover:-translate-y-1
                              {{ $index === 0 ? 'col-span-2 aspect-video' : 'aspect-square' }}
                              {{ $index === 1 ? 'mt-6' : '' }}
                              {{ $index === 3 ? '-mt-6' : '' }}">
                        <img src="{{ $product->images->first()->url() }}" alt="{{ $product->name }}"
                             class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    <div class="absolute inset-x-0 bottom-0 h-12 bg-white [clip-path:polygon(0_100%,100%_0,100%_100%)]"></div>
</section>

{{-- Real, sourced facts — see docs/DECISIONS.md --}}
<section class="border-b border-neutral-100 bg-white py-12">
    <div class="mx-auto grid max-w-6xl grid-cols-2 gap-8 px-4 text-center sm:px-6 lg:grid-cols-4">
        <div data-animate>
            <p class="mb-1 text-3xl font-bold text-brand-accent-dark" dir="ltr">
                <span data-counter="110" data-counter-suffix="+">۱۱۰+</span>
            </p>
            <p class="text-xs text-neutral-500">تنوع محصول</p>
        </div>
        <div data-animate>
            <p class="mb-1 text-3xl font-bold text-brand-accent-dark" dir="ltr">
                <span data-counter="1000" data-counter-suffix="+">۱۰۰۰+</span>
            </p>
            <p class="text-xs text-neutral-500">قرارداد موفق</p>
        </div>
        <div data-animate>
            <p class="mb-1 text-3xl font-bold text-brand-accent-dark" dir="ltr">
                <span data-counter="97" data-counter-suffix="٪">۹۷٪</span>
            </p>
            <p class="text-xs text-neutral-500">تضمین کیفیت</p>
        </div>
        <div data-animate>
            <p class="mb-1 text-3xl font-bold text-brand-accent-dark">۱۳۷۴</p>
            <p class="text-xs text-neutral-500">سال تأسیس</p>
        </div>
    </div>
</section>

{{-- Categories --}}
<section id="categories" class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
    <div data-animate class="mb-10 max-w-xl">
        <h2 class="mb-2 text-xl font-bold text-neutral-900">دسته‌بندی محصولات</h2>
        <p class="text-sm text-neutral-500">بر اساس کاربرد صنعتی، محصول مناسب خود را پیدا کنید.</p>
    </div>

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        @foreach ($categories as $category)
            <a href="{{ route('categories.show', $category) }}" data-animate
               class="group relative overflow-hidden rounded-lg border border-neutral-200 bg-white p-5 transition hover:-translate-y-0.5 hover:border-brand-accent hover:shadow-lg hover:shadow-neutral-200/60">
                <span class="absolute inset-x-0 top-0 h-0.5 scale-x-0 bg-brand-accent transition group-hover:scale-x-100"></span>
                <h3 class="mb-1 font-medium text-neutral-900 group-hover:text-brand-accent-dark">{{ $category->name }}</h3>
                <p class="text-xs text-neutral-500">{{ $category->primary_products_count }} محصول</p>
            </a>
        @endforeach
    </div>
</section>

{{-- Featured products --}}
@if ($products->isNotEmpty())
<section class="bg-neutral-50 py-16">
    <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <div data-animate class="mb-10 max-w-xl">
            <h2 class="mb-2 text-xl font-bold text-neutral-900">محصولات</h2>
            <p class="text-sm text-neutral-500">نمونه‌ای از محصولات موجود — برای قیمت و مشخصات دقیق با ما در ارتباط باشید.</p>
        </div>

        <div class="grid grid-cols-2 gap-5 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($products as $product)
                <a href="{{ route('products.show', $product) }}" data-animate
                   class="group rounded-lg border border-neutral-200 bg-white p-3 transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-neutral-200/60">
                    <div class="mb-3 aspect-square overflow-hidden rounded bg-neutral-100">
                        @if ($product->images->isNotEmpty())
                            <img src="{{ $product->images->first()->url() }}" alt="{{ $product->name }}"
                                 class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
                        @else
                            <div class="flex h-full w-full items-center justify-center text-xs text-neutral-400">بدون تصویر</div>
                        @endif
                    </div>
                    <h3 class="mb-1 text-sm font-medium text-neutral-900 group-hover:text-brand-accent-dark">{{ $product->name }}</h3>
                    <p class="text-xs text-neutral-500">{{ $product->primaryCategory?->name }}</p>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Capabilities --}}
<section class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
    <h2 data-animate class="mb-10 text-xl font-bold text-neutral-900">چرا پاسارگاد برس؟</h2>
    <div class="grid gap-6 sm:grid-cols-3">
        <div data-animate class="relative rounded-lg border border-neutral-200 p-6 pt-10">
            <span class="absolute right-6 top-4 text-3xl font-bold text-neutral-100">۰۱</span>
            <h3 class="relative mb-2 font-medium text-neutral-900">ساخت سفارشی</h3>
            <p class="relative text-sm text-neutral-500">محصولات بر اساس ابعاد، نوع تار و کاربرد دقیق شما تولید می‌شوند.</p>
        </div>
        <div data-animate class="relative rounded-lg border border-neutral-200 p-6 pt-10">
            <span class="absolute right-6 top-4 text-3xl font-bold text-neutral-100">۰۲</span>
            <h3 class="relative mb-2 font-medium text-neutral-900">تنوع کاربردی</h3>
            <p class="relative text-sm text-neutral-500">از صنایع غذایی و نساجی تا نفت، پتروشیمی و قالیشویی.</p>
        </div>
        <div data-animate class="relative rounded-lg border border-neutral-200 p-6 pt-10">
            <span class="absolute right-6 top-4 text-3xl font-bold text-neutral-100">۰۳</span>
            <h3 class="relative mb-2 font-medium text-neutral-900">ارتباط مستقیم با تولیدکننده</h3>
            <p class="relative text-sm text-neutral-500">مشاوره و استعلام قیمت بدون واسطه، مستقیم با تیم فنی.</p>
        </div>
    </div>
</section>

{{-- Articles --}}
@if ($posts->isNotEmpty())
<section class="bg-neutral-50 py-16">
    <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <div data-animate class="mb-10 flex items-center justify-between">
            <h2 class="text-xl font-bold text-neutral-900">مقالات</h2>
            <a href="{{ route('blog.index') }}" class="text-sm font-medium text-brand-accent-dark hover:underline">همه مقالات</a>
        </div>
        <div class="grid gap-5 sm:grid-cols-3">
            @foreach ($posts as $post)
                <a href="{{ route('blog.show', $post) }}" data-animate
                   class="group rounded-lg border border-neutral-200 bg-white p-4 transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-neutral-200/60">
                    <div class="mb-3 aspect-video overflow-hidden rounded bg-neutral-100">
                        @if ($post->featuredImage->isNotEmpty())
                            <img src="{{ $post->featuredImage->first()->url() }}" alt="{{ $post->title }}"
                                 class="h-full w-full object-cover" loading="lazy">
                        @endif
                    </div>
                    <h3 class="text-sm font-medium text-neutral-900 group-hover:text-brand-accent-dark">{{ $post->title }}</h3>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif
@endsection
