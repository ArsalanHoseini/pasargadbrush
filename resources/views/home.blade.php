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

@section('content')
{{-- Hero --}}
<section class="bg-brand-charcoal">
    <div class="mx-auto grid max-w-6xl items-center gap-10 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:py-24">
        <div class="text-white">
            <h1 class="mb-4 text-3xl font-bold leading-tight sm:text-4xl">
                تولیدکننده انواع برس و فرچه صنعتی
            </h1>
            <p class="mb-8 max-w-md text-neutral-300">
                از برس‌های ابزاری و قالیشویی تا نمونه‌های سفارشی برای صنایع غذایی، نفت و پتروشیمی، نساجی و بیش از ۴۰ کاربرد دیگر — ساخته‌شده بر اساس نیاز دقیق شما.
            </p>
            <div class="flex flex-wrap gap-3">
                <a href="#categories" class="rounded-full bg-brand-accent px-6 py-3 text-sm font-semibold text-white hover:bg-brand-accent-dark">
                    مشاهده محصولات
                </a>
                <a href="#contact" class="rounded-full border border-neutral-600 px-6 py-3 text-sm font-semibold text-white hover:border-neutral-400">
                    درخواست قیمت و مشاوره
                </a>
            </div>
        </div>
        @if ($products->isNotEmpty() && $products->first()->images->isNotEmpty())
            <img src="{{ $products->first()->images->first()->url() }}"
                 alt="{{ $products->first()->name }}"
                 class="aspect-4/3 w-full rounded-xl bg-white object-cover"
                 width="640" height="480">
        @endif
    </div>
</section>

{{-- Categories --}}
<section id="categories" class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
    <h2 class="mb-2 text-xl font-bold text-neutral-900">دسته‌بندی محصولات</h2>
    <p class="mb-8 text-sm text-neutral-500">بر اساس کاربرد صنعتی، محصول مناسب خود را پیدا کنید.</p>

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        @foreach ($categories as $category)
            <a href="{{ route('categories.show', $category) }}"
               class="group rounded-lg border border-neutral-200 p-5 transition hover:border-brand-accent hover:shadow-sm">
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
        <h2 class="mb-2 text-xl font-bold text-neutral-900">محصولات</h2>
        <p class="mb-8 text-sm text-neutral-500">نمونه‌ای از محصولات موجود — برای قیمت و مشخصات دقیق با ما در ارتباط باشید.</p>

        <div class="grid grid-cols-2 gap-5 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($products as $product)
                <a href="{{ route('products.show', $product) }}" class="group rounded-lg border border-neutral-200 bg-white p-3 transition hover:shadow-md">
                    <div class="mb-3 aspect-square overflow-hidden rounded bg-neutral-100">
                        @if ($product->images->isNotEmpty())
                            <img src="{{ $product->images->first()->url() }}" alt="{{ $product->name }}"
                                 class="h-full w-full object-cover transition group-hover:scale-105" loading="lazy">
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
    <h2 class="mb-8 text-xl font-bold text-neutral-900">چرا پاسارگاد برس؟</h2>
    <div class="grid gap-6 sm:grid-cols-3">
        <div class="rounded-lg border border-neutral-200 p-6">
            <h3 class="mb-2 font-medium text-neutral-900">ساخت سفارشی</h3>
            <p class="text-sm text-neutral-500">محصولات بر اساس ابعاد، نوع تار و کاربرد دقیق شما تولید می‌شوند.</p>
        </div>
        <div class="rounded-lg border border-neutral-200 p-6">
            <h3 class="mb-2 font-medium text-neutral-900">تنوع کاربردی</h3>
            <p class="text-sm text-neutral-500">از صنایع غذایی و نساجی تا نفت، پتروشیمی و قالیشویی.</p>
        </div>
        <div class="rounded-lg border border-neutral-200 p-6">
            <h3 class="mb-2 font-medium text-neutral-900">ارتباط مستقیم با تولیدکننده</h3>
            <p class="text-sm text-neutral-500">مشاوره و استعلام قیمت بدون واسطه، مستقیم با تیم فنی.</p>
        </div>
    </div>
</section>

{{-- Articles --}}
@if ($posts->isNotEmpty())
<section class="bg-neutral-50 py-16">
    <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <div class="mb-8 flex items-center justify-between">
            <h2 class="text-xl font-bold text-neutral-900">مقالات</h2>
            <a href="{{ route('blog.index') }}" class="text-sm font-medium text-brand-accent-dark hover:underline">همه مقالات</a>
        </div>
        <div class="grid gap-5 sm:grid-cols-3">
            @foreach ($posts as $post)
                <a href="{{ route('blog.show', $post) }}" class="group rounded-lg border border-neutral-200 bg-white p-4 transition hover:shadow-md">
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
