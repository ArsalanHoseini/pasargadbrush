@extends('layouts.public')

@section('title', 'خرید برس و فرچه صنعتی | تولیدکننده مستقیم')
@section('meta_description', 'برس پاسارگاد، تولیدکننده انواع برس و فرچه صنعتی از سال ۱۳۷۴: برس ابزاری، قالیشویی و نظافتی، هویج‌شویی و سورتینگ، نواری و درزگیر، صنایع غذایی. خرید برس و خرید فرچه سفارشی، مستقیم از کارخانه و بدون واسطه.')

@php
    app(\App\Services\Seo\SchemaGraph::class)->add(
        \App\Services\Seo\StructuredData::webPage('WebPage', route('home'), 'خرید برس و فرچه صنعتی | تولیدکننده مستقیم', \App\Support\SiteProfile::description(), extra: [
            'mainEntity' => ['@id' => \App\Services\Seo\StructuredData::organizationId()],
        ]),
        \App\Services\Seo\StructuredData::catalog($catalogCategories),
    );
@endphp

@php
$heroProductKeywords = ['سیلندری', 'دیسکی', 'سورتینگ', 'صنایع'];
$mosaicProducts = $products
    ->filter(fn ($product) => $product->images->isNotEmpty())
    ->sortBy(function ($product) use ($heroProductKeywords) {
        foreach ($heroProductKeywords as $priority => $keyword) {
            if (str_contains($product->name, $keyword)) {
                return $priority;
            }
        }

        return count($heroProductKeywords);
    })
    ->take(3)
    ->values();
@endphp

@section('content')
{{-- Hero --}}
<section class="hero-industrial relative isolate overflow-hidden bg-brand-charcoal-deep text-white">
    <div class="bg-grid-industrial pointer-events-none absolute inset-0 opacity-25" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -left-36 top-1/3 h-96 w-96 rounded-full bg-brand-accent/15 blur-3xl" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -right-44 -top-44 h-[32rem] w-[32rem] rounded-full border border-white/10" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -right-24 -top-24 h-80 w-80 rounded-full border border-brand-accent/15" aria-hidden="true"></div>

    <div class="relative mx-auto grid min-h-[42rem] max-w-6xl items-center gap-14 px-4 pb-24 pt-16 sm:px-6 sm:pt-20 lg:grid-cols-[1.02fr_.98fr] lg:gap-20 lg:pb-28 lg:pt-24">
        <div class="max-w-2xl">
            <div class="mb-6 inline-flex items-center gap-3 rounded-full border border-brand-accent/25 bg-brand-accent/10 px-4 py-2 text-xs font-semibold text-orange-200 backdrop-blur-sm">
                <span class="relative flex h-2 w-2" aria-hidden="true">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-brand-accent opacity-60 motion-reduce:animate-none"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-brand-accent"></span>
                </span>
                برس صنعت پاسارگاد · تولید مستقیم از سال ۱۳۷۴
            </div>

            <h1 class="mb-6 text-4xl font-bold leading-[1.2] tracking-tight sm:text-5xl lg:text-[3.65rem]">
                <span class="block text-white">برس صنعتی،</span>
                <span class="block bg-gradient-to-l from-brand-accent via-orange-300 to-brand-gold bg-clip-text text-transparent">دقیقاً برای خط تولید شما</span>
            </h1>

            <p class="mb-3 text-sm font-semibold text-neutral-200">تولیدکننده و فروشنده برس و فرچه صنعتی</p>
            <p class="mb-8 max-w-xl text-pretty text-sm leading-7 text-neutral-400 sm:text-base sm:leading-8">
                از انتخاب نوع تار و ابعاد تا ساخت نمونه سفارشی، تیم فنی ما کمک می‌کند برس متناسب با دستگاه و کاربردتان را مستقیم از کارخانه تهیه کنید.
            </p>

            <div class="mb-9 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                <a href="#products" class="group inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-brand-accent-dark px-7 py-3.5 text-sm font-semibold text-white shadow-[0_16px_40px_-16px_rgba(255,119,0,.75)] transition hover:-translate-y-0.5 hover:bg-brand-accent-darker focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand-accent">
                    مشاهده و خرید برس صنعتی
                    <svg class="h-4 w-4 transition group-hover:-translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M15 6l-6 6 6 6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
                <a href="#contact" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl border border-white/20 bg-white/5 px-7 py-3.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:-translate-y-0.5 hover:border-white/40 hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white">
                    <svg class="h-4 w-4 text-brand-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M8 10h8M8 14h5" stroke-linecap="round"/>
                        <path d="M20 12a8 8 0 0 1-8 8H6l-3 2 1-4.2A8 8 0 1 1 20 12Z" stroke-linejoin="round"/>
                    </svg>
                    مشاوره و استعلام قیمت
                </a>
            </div>

            <dl class="grid grid-cols-3 divide-x divide-x-reverse divide-white/10 border-t border-white/10 pt-6 text-right">
                <div class="px-3 first:pr-0">
                    <dt class="mb-1 text-xs text-neutral-500">سابقه تولید</dt>
                    <dd class="text-sm font-bold text-neutral-100 sm:text-base">از ۱۳۷۴</dd>
                </div>
                <div class="px-3">
                    <dt class="mb-1 text-xs text-neutral-500">نوع سفارش</dt>
                    <dd class="text-sm font-bold text-neutral-100 sm:text-base">استاندارد و سفارشی</dd>
                </div>
                <div class="px-3 last:pl-0">
                    <dt class="mb-1 text-xs text-neutral-500">مسیر ارتباط</dt>
                    <dd class="text-sm font-bold text-neutral-100 sm:text-base">مستقیم با تولیدکننده</dd>
                </div>
            </dl>
        </div>

        @if ($mosaicProducts->isNotEmpty())
            <div class="relative mx-auto w-full max-w-[31rem] lg:mx-0">
                <div class="absolute -inset-4 rounded-[2rem] border border-white/10 bg-white/[.025] backdrop-blur-sm" aria-hidden="true"></div>
                <div class="absolute -bottom-5 -right-5 h-24 w-24 rounded-3xl bg-brand-accent/20 blur-2xl" aria-hidden="true"></div>

                <div class="relative grid grid-cols-[1fr_.54fr] gap-3 sm:gap-4">
                    <a href="{{ route('products.show', $mosaicProducts[0]) }}"
                       class="group relative row-span-2 block min-h-[22rem] overflow-hidden rounded-[1.5rem] bg-neutral-800 shadow-2xl shadow-black/50 ring-1 ring-white/10 sm:min-h-[28rem]">
                        <img src="{{ $mosaicProducts[0]->images->first()->thumbnailUrl(960) }}" alt="{{ $mosaicProducts[0]->name }}"
                             width="560" height="720" fetchpriority="high"
                             class="absolute inset-0 h-full w-full object-cover transition duration-700 group-hover:scale-105">
                        <span class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/5 to-transparent"></span>
                        <span class="absolute inset-x-0 bottom-0 flex items-end justify-between gap-3 p-5">
                            <span>
                                <span class="mb-1 block text-[.68rem] text-orange-200">پیشنهاد برای مشاهده</span>
                                <span class="block text-sm font-bold text-white sm:text-base">{{ $mosaicProducts[0]->name }}</span>
                            </span>
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full border border-white/20 bg-black/25 transition group-hover:-translate-x-1 group-hover:border-brand-accent group-hover:bg-brand-accent-dark" aria-hidden="true">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 6l-6 6 6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </span>
                        </span>
                    </a>

                    @foreach ($mosaicProducts->slice(1, 2) as $product)
                        <a href="{{ route('products.show', $product) }}"
                           class="group relative block min-h-[10.5rem] overflow-hidden rounded-[1.25rem] bg-neutral-800 shadow-xl shadow-black/40 ring-1 ring-white/10 sm:min-h-0 sm:aspect-square">
                            <img src="{{ $product->images->first()->thumbnailUrl(480) }}" alt="{{ $product->name }}"
                                 width="320" height="320"
                                 class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-105" decoding="async">
                            <span class="absolute inset-0 bg-gradient-to-t from-black/85 via-transparent to-transparent"></span>
                            <span class="absolute inset-x-0 bottom-0 p-3 text-xs font-semibold leading-5 text-white sm:p-4">{{ $product->name }}</span>
                        </a>
                    @endforeach

                    @if ($mosaicProducts->count() === 1)
                        <div class="row-span-2 grid place-items-center rounded-[1.25rem] border border-dashed border-white/15 bg-white/[.03] p-4 text-center text-xs leading-6 text-neutral-400">
                            نمونه‌های بیشتر را در فهرست محصولات ببینید
                        </div>
                    @endif
                </div>

                <div class="absolute -left-3 top-6 hidden items-center gap-2 rounded-xl border border-white/15 bg-brand-charcoal/90 px-3 py-2 text-xs font-medium text-neutral-100 shadow-xl backdrop-blur-md sm:flex">
                    <svg class="h-4 w-4 text-brand-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 3 4 7v5c0 4.6 3.4 7.5 8 9 4.6-1.5 8-4.4 8-9V7l-8-4Z"/><path d="m9 12 2 2 4-4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    ساخت متناسب با کاربرد شما
                </div>
            </div>
        @endif
    </div>

    <div class="absolute inset-x-0 bottom-0 h-8 bg-white [clip-path:polygon(0_100%,100%_0,100%_100%)] sm:h-12" aria-hidden="true"></div>
</section>

{{-- Brush types strip from the previous WordPress homepage, right below the hero (owner request 2026-11-07). --}}
@if ($brushTypes)
<section class="border-b border-neutral-100 bg-white" aria-label="انواع برس">
    <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 sm:py-8">
        <div class="mb-4 flex items-end justify-between gap-4 sm:mb-5">
            <div>
                <p class="mb-1 text-[.68rem] font-bold tracking-wider text-brand-accent-dark">انتخاب سریع</p>
                <h2 class="text-base font-bold text-neutral-900 sm:text-lg">بر اساس نوع برس شروع کنید</h2>
            </div>
            <span class="shrink-0 text-[.68rem] text-neutral-400 sm:hidden">برای مشاهده بکشید ←</span>
        </div>
        <ul class="flex snap-x snap-mandatory gap-3 overflow-x-auto pb-2 [scrollbar-width:none] sm:grid sm:grid-cols-6 sm:overflow-visible sm:pb-0">
            @foreach ($brushTypes as $type)
                <li class="w-32 shrink-0 snap-start sm:w-auto">
                    <a href="{{ route('products.show', $type['slug']) }}" class="group block rounded-2xl border border-transparent p-1 text-center transition hover:-translate-y-1 hover:border-orange-100 hover:bg-orange-50/50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-accent">
                        <span class="mb-2 block aspect-square overflow-hidden rounded-xl border border-neutral-200 bg-neutral-50 transition group-hover:border-brand-accent/50 group-hover:bg-white group-hover:shadow-lg group-hover:shadow-orange-100/70">
                            <img src="{{ asset('images/brush-types/'.$type['image']) }}" alt="" width="320" height="320"
                                 class="h-full w-full object-contain transition duration-300 group-hover:scale-105">
                        </span>
                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-neutral-800 group-hover:text-brand-accent-dark sm:text-sm">
                            {{ $type['name'] }}
                            <svg class="h-3 w-3 opacity-0 transition group-hover:-translate-x-0.5 group-hover:opacity-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 6l-6 6 6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif

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

{{-- Capabilities — the same cards /llms.txt and the schema read (config/site.php «highlights»). --}}
<section id="why-us" class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
    <h2 data-animate class="mb-10 text-xl font-bold text-neutral-900">چرا پاسارگاد برس؟</h2>
    <div class="grid gap-6 sm:grid-cols-3">
        @foreach (config('site.highlights') as $highlight)
            <div data-animate class="relative rounded-lg border border-neutral-200 p-6 pt-10">
                <span class="absolute right-6 top-4 text-3xl font-bold text-neutral-100 before:content-[attr(data-number)]" data-number="{{ \App\Support\PersianDigits::toPersian(str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT)) }}" aria-hidden="true"></span>
                <h3 class="relative mb-2 font-medium text-neutral-900">{{ $highlight['title'] }}</h3>
                <p class="relative text-sm text-neutral-500">{{ $highlight['text'] }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- SEO/conversion text — real facts already sourced for the About Us page, see docs/DECISIONS.md --}}
<section id="about" class="bg-brand-charcoal-deep py-16">
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
<section id="articles" class="bg-neutral-50 py-16">
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
