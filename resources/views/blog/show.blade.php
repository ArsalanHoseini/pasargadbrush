@extends('layouts.public')

@section('title', $post->meta_title ?? $post->title)
@section('meta_description', $post->meta_description ?? \Illuminate\Support\Str::limit(strip_tags($post->body), 150))
@section('canonical', url()->current())

@push('structured-data')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BlogPosting",
    "headline": {!! json_encode($post->title, JSON_UNESCAPED_UNICODE) !!},
    "datePublished": {!! json_encode($post->published_at?->toIso8601String()) !!}
    @if ($post->featuredImage->isNotEmpty())
    ,"image": {!! json_encode($post->featuredImage->first()->url()) !!}
    @endif
}
</script>
@endpush

@section('content')
<article class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
    <nav class="mb-6 text-xs text-neutral-500" aria-label="مسیر صفحه">
        <a href="{{ route('home') }}" class="hover:text-neutral-800">صفحه اصلی</a>
        <span class="mx-1">/</span>
        <a href="{{ route('blog.index') }}" class="hover:text-neutral-800">مقالات</a>
    </nav>

    <h1 class="mb-3 text-2xl font-bold text-neutral-900">{{ $post->title }}</h1>
    <p class="mb-8 text-xs text-neutral-500">{{ $post->published_at?->format('Y-m-d') }}</p>

    @if ($post->featuredImage->isNotEmpty())
        <div class="mb-8 aspect-video overflow-hidden rounded-lg bg-neutral-100">
            <img src="{{ $post->featuredImage->first()->url() }}" alt="{{ $post->title }}" class="h-full w-full object-cover">
        </div>
    @endif

    <div class="article-content text-sm leading-7 text-neutral-700">
        {!! $post->body !!}
    </div>

    @if ($related->isNotEmpty())
        <div class="mt-12 border-t border-neutral-100 pt-8">
            <h2 class="mb-4 text-sm font-bold text-neutral-900">مقالات مرتبط</h2>
            <ul class="space-y-2 text-sm">
                @foreach ($related as $item)
                    <li><a href="{{ route('blog.show', $item) }}" class="text-brand-accent-dark hover:underline">{{ $item->title }}</a></li>
                @endforeach
            </ul>
        </div>
    @endif
</article>
@endsection
