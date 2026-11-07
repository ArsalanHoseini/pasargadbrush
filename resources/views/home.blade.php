@extends('layouts.public')

@section('title', 'خرید برس و فرچه صنعتی | تولیدکننده مستقیم')
@section('meta_description', 'برس پاسارگاد، تولیدکننده انواع برس و فرچه صنعتی از سال ۱۳۷۴: برس ابزاری، قالیشویی و نظافتی، هویج‌شویی و سورتینگ، نواری و درزگیر، صنایع غذایی. خرید برس و خرید فرچه سفارشی، مستقیم از کارخانه و بدون واسطه.')

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
$mosaicProducts = $products->filter(fn ($product) => $product->images->isNotEmpty())->take(3)->values();
@endphp

@section('content')
{{-- Brush types strip — as on the previous WordPress homepage, right under the header (owner request 2026-11-07). --}}
@if ($brushTypes)
<section class="border-b border-neutral-100 bg-white" aria-label="انواع برس">
    <div class="mx-auto max-w-6xl px-4 py-4 sm:px-6">
        <ul class="flex snap-x gap-3 overflow-x-auto pb-1 [scrollbar-width:none] sm:grid sm:grid-cols-6 sm:overflow-visible">
            @foreach ($brushTypes as $type)
                <li class="w-28 shrink-0 snap-start sm:w-auto">
                    <a href="{{ route('products.show', $type['slug']) }}" class="group block text-center">
                        <span class="mb-2 block aspect-square overflow-hidden rounded-xl border border-neutral-200 bg-white transition group-hover:border-brand-accent">
                            <img src="{{ asset('images/brush-types/'.$type['image']) }}" alt="" width="320" height="320"
                                 class="h-full w-full object-contain transition duration-300 group-hover:scale-105">
                        </span>
                        <span class="text-xs font-medium text-neutral-800 group-hover:text-brand-accent-dark sm:text-sm">{{ $type['name'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif

{{-- Hero --}}
<section class="relative overflow-hidden bg-brand-charcoal-deep">
    <div class="bg-grid-industrial absolute inset-0 opacity-40"></div>
    <div class="absolute inset-0 bg-gradient-to-t from-brand-charcoal-deep via-transparent to-transparent"></div>
    <div class="absolute left-1/2 top-0 h-[32rem] w-[32rem] -translate-x-1/2 rounded-full bg-brand-accent/10 blur-3xl"></div>

    <div class="relative mx-auto grid max-w-6xl items-center gap-12 px-4 py-20 sm:px-6 lg:grid-cols-2 lg:py-32">
        <div class="text-white">
            <div class="mb-5 inline-flex items-center gap-2 text-xs font-semibold tracking-widest text-brand-accent">
                <span class="h-px w-6 bg-brand-accent"></span>
                برس پاسارگاد · از سال ۱۳۷۴
            </div>
            <h1 class="mb-5 text-4xl font-bold leading-[1.15] sm:text-5xl lg:text-6xl">
                <span class="block text-brand-accent">برس پاسارگاد</span>
                <span class="block">تولیدکننده و فروشنده برس و فرچه صنعتی</span>
            </h1>
            <p class="mb-9 max-w-md text-balance leading-7 text-neutral-400">
                خرید برس صنعتی و خرید فرچه با مشخصات دقیق، مستقیم از کارخانه و بدون واسطه — از برس‌های ابزاری و قالیشویی تا نمونه‌های سفارشی برای صنایع غذایی، نفت و پتروشیمی و نساجی.
            </p>
            <div class="flex flex-wrap items-center gap-3">
                <a href="#products" class="group inline-flex items-center gap-2 rounded-full bg-brand-accent-dark px-7 py-3.5 text-sm font-semibold text-white shadow-lg shadow-brand-accent-dark/30 transition hover:bg-brand-accent-darker">
                    خرید برس و فرچه صنعتی
                    <svg class="h-4 w-4 transition group-hover:-translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M15 6l-6 6 6 6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
                <a href="#contact" class="rounded-full border border-white/20 px-7 py-3.5 text-sm font-semibold text-white transition hover:border-white/40 hover:bg-white/5">
                    درخواست قیمت و مشاوره
                </a>
            </div>
        </div>

        @if ($mosaicProducts->count() >= 3)
            <div class="relative mx-auto flex max-w-md gap-4 lg:max-w-none">
                <a href="{{ route('products.show', $mosaicProducts[0]) }}"
                   class="group block w-3/5 overflow-hidden rounded-2xl bg-neutral-800 shadow-2xl shadow-black/40 transition hover:-translate-y-1">
                    <div class="aspect-3/4 overflow-hidden">
                        <img src="{{ $mosaicProducts[0]->images->first()->thumbnailUrl(960) }}" alt="{{ $mosaicProducts[0]->name }}"
                             width="480" height="640" fetchpriority="high"
                             class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                    </div>
                </a>
                <div class="flex w-2/5 flex-col gap-4">
                    @foreach ($mosaicProducts->slice(1, 2) as $product)
                        <a href="{{ route('products.show', $product) }}"
                           class="group block overflow-hidden rounded-2xl bg-neutral-800 shadow-2xl shadow-black/40 transition hover:-translate-y-1">
                            <div class="aspect-square overflow-hidden">
                                <img src="{{ $product->images->first()->thumbnailUrl(480) }}" alt="{{ $product->name }}"
                                     width="320" height="320"
                                     class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @elseif ($mosaicProducts->isNotEmpty())
            <a href="{{ route('products.show', $mosaicProducts[0]) }}"
               class="group mx-auto block max-w-md overflow-hidden rounded-2xl bg-neutral-800 shadow-2xl shadow-black/40 transition hover:-translate-y-1 lg:max-w-none">
                <div class="aspect-4/3 overflow-hidden">
                    <img src="{{ $mosaicProducts[0]->images->first()->thumbnailUrl(960) }}" alt="{{ $mosaicProducts[0]->name }}"
                         width="640" height="480" fetchpriority="high"
                         class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                </div>
            </a>
        @endif
    </div>

    <div class="absolute inset-x-0 bottom-0 h-8 bg-white [clip-path:polygon(0_100%,100%_0,100%_100%)] sm:h-12"></div>
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

{{-- Featured products --}}
@if ($products->isNotEmpty())
<section id="products" class="bg-neutral-50 py-16">
    <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <div data-animate class="mb-10 max-w-xl">
            <h2 class="mb-2 text-xl font-bold text-neutral-900">محصولات</h2>
            <p class="text-sm text-neutral-500">نمونه‌ای از محصولات موجود — برای قیمت و مشخصات دقیق با ما در ارتباط باشید.</p>
        </div>

        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 sm:gap-5 lg:grid-cols-4">
            @foreach ($products as $product)
                @include('partials.product-card', ['product' => $product])
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
            <span class="absolute right-6 top-4 text-3xl font-bold text-neutral-100 before:content-[attr(data-number)]" data-number="۰۱" aria-hidden="true"></span>
            <h3 class="relative mb-2 font-medium text-neutral-900">ساخت سفارشی</h3>
            <p class="relative text-sm text-neutral-500">محصولات بر اساس ابعاد، نوع تار و کاربرد دقیق شما تولید می‌شوند.</p>
        </div>
        <div data-animate class="relative rounded-lg border border-neutral-200 p-6 pt-10">
            <span class="absolute right-6 top-4 text-3xl font-bold text-neutral-100 before:content-[attr(data-number)]" data-number="۰۲" aria-hidden="true"></span>
            <h3 class="relative mb-2 font-medium text-neutral-900">تنوع کاربردی</h3>
            <p class="relative text-sm text-neutral-500">از صنایع غذایی و نساجی تا نفت، پتروشیمی و قالیشویی.</p>
        </div>
        <div data-animate class="relative rounded-lg border border-neutral-200 p-6 pt-10">
            <span class="absolute right-6 top-4 text-3xl font-bold text-neutral-100 before:content-[attr(data-number)]" data-number="۰۳" aria-hidden="true"></span>
            <h3 class="relative mb-2 font-medium text-neutral-900">ارتباط مستقیم با تولیدکننده</h3>
            <p class="relative text-sm text-neutral-500">مشاوره و استعلام قیمت بدون واسطه، مستقیم با تیم فنی.</p>
        </div>
    </div>
</section>

{{-- SEO/conversion text — real facts already sourced for the About Us page, see docs/DECISIONS.md --}}
<section class="bg-brand-charcoal-deep py-16">
    <div class="mx-auto max-w-2xl px-4 text-center sm:px-6">
        <h2 data-animate class="mb-4 text-lg font-bold text-white">خرید برس و فرچه صنعتی از برس پاسارگاد</h2>
        <p data-animate class="mb-7 leading-7 text-neutral-400">
            برس پاسارگاد (شرکت برس صنعت پاسارگاد) از سال ۱۳۷۴ تولیدکننده انواع برس و فرچه صنعتی در ایران است. برای خرید برس یا خرید فرچه با مشخصات دقیق و قیمت مناسب، سفارش خود را مستقیم از کارخانه و بدون واسطه ثبت کنید؛ تیم فنی ما برای هر کاربرد — از صنایع غذایی و نساجی تا نفت و پتروشیمی — محصول متناسب با نیاز شما را پیشنهاد می‌دهد.
        </p>
        <a href="#products" data-animate
           class="inline-flex items-center gap-2 rounded-full border border-white/20 px-6 py-3 text-sm font-semibold text-white transition hover:border-white/40 hover:bg-white/5">
            مشاهده محصولات
        </a>
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
                            <img src="{{ $post->featuredImage->first()->thumbnailUrl(480) }}" alt="" width="480" height="270"
                                 class="h-full w-full object-cover" loading="lazy" decoding="async">
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
