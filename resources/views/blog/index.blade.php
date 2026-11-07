@extends('layouts.public')

@section('title', isset($tag) ? 'مقالات با برچسب «'.$tag->name.'»' : 'مقالات')
@section('meta_description', 'مقالات و راهنمای فنی برس و فرچه صنعتی')

@section('content')
<div class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
    <div class="mb-8 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-neutral-900">
            @isset($tag)
                مقالات با برچسب «{{ $tag->name }}»
            @else
                مقالات
            @endisset
        </h1>
        <a href="{{ route('blog.feed') }}" class="text-xs text-neutral-500 hover:text-brand-accent-dark">خوراک RSS</a>
    </div>

    @if (! empty($featured))
        <a href="{{ route('blog.show', $featured) }}"
           class="mb-8 flex flex-col gap-4 rounded-lg border border-neutral-200 bg-white p-4 transition hover:shadow-md sm:flex-row">
            <div class="aspect-video w-full overflow-hidden rounded bg-neutral-100 sm:w-72 sm:shrink-0">
                @if ($featured->featuredImage->isNotEmpty())
                    <img src="{{ $featured->featuredImage->first()->thumbnailUrl(480) }}" alt="" width="480" height="270"
                         class="h-full w-full object-cover" loading="lazy">
                @endif
            </div>
            <div>
                <p class="mb-1 text-xs font-semibold text-brand-accent-dark">مقاله ویژه</p>
                <h2 class="mb-2 text-lg font-bold text-neutral-900">{{ $featured->title }}</h2>
                <p class="text-sm text-neutral-600">{{ $featured->excerpt }}</p>
            </div>
        </a>
    @endif

    @if ($posts->isEmpty())
        <p class="text-sm text-neutral-500">هنوز مقاله‌ای منتشر نشده است.</p>
    @else
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($posts as $post)
                <a href="{{ route('blog.show', $post) }}" class="group rounded-lg border border-neutral-200 bg-white p-4 transition hover:shadow-md">
                    <div class="mb-3 aspect-video overflow-hidden rounded bg-neutral-100">
                        @if ($post->featuredImage->isNotEmpty())
                            <img src="{{ $post->featuredImage->first()->thumbnailUrl(480) }}" alt="" width="480" height="270"
                                 class="h-full w-full object-cover" loading="lazy">
                        @endif
                    </div>
                    @if ($post->category)
                        <p class="mb-1 text-xs text-brand-accent-dark">{{ $post->category->name }}</p>
                    @endif
                    <h2 class="mb-1 text-sm font-medium text-neutral-900 group-hover:text-brand-accent-dark">{{ $post->title }}</h2>
                    <p class="mb-2 text-xs text-neutral-500"><time datetime="{{ $post->published_at?->toIso8601String() }}">{{ persian_date($post->published_at, \App\Support\PersianDate::DATE) }}</time> · {{ $post->reading_time }} دقیقه مطالعه</p>
                    @if ($post->tags->isNotEmpty())
                        <p class="text-xs text-neutral-400">
                            @foreach ($post->tags->take(3) as $postTag)
                                #{{ $postTag->name }}
                            @endforeach
                        </p>
                    @endif
                </a>
            @endforeach
        </div>

        <div class="mt-8">{{ $posts->links() }}</div>
    @endif
</div>
@endsection
