@extends('layouts.public')

@section('title', 'مقالات')
@section('meta_description', 'مقالات و راهنمای فنی برس و فرچه صنعتی')

@section('content')
<div class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
    <h1 class="mb-8 text-2xl font-bold text-neutral-900">مقالات</h1>

    @if ($posts->isEmpty())
        <p class="text-sm text-neutral-500">هنوز مقاله‌ای منتشر نشده است.</p>
    @else
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($posts as $post)
                <a href="{{ route('blog.show', $post) }}" class="group rounded-lg border border-neutral-200 bg-white p-4 transition hover:shadow-md">
                    <div class="mb-3 aspect-video overflow-hidden rounded bg-neutral-100">
                        @if ($post->featuredImage->isNotEmpty())
                            <img src="{{ $post->featuredImage->first()->url() }}" alt="{{ $post->title }}"
                                 class="h-full w-full object-cover" loading="lazy">
                        @endif
                    </div>
                    @if ($post->category)
                        <p class="mb-1 text-xs text-brand-accent-dark">{{ $post->category->name }}</p>
                    @endif
                    <h2 class="mb-1 text-sm font-medium text-neutral-900 group-hover:text-brand-accent-dark">{{ $post->title }}</h2>
                    <p class="text-xs text-neutral-500">{{ $post->published_at?->format('Y-m-d') }}</p>
                </a>
            @endforeach
        </div>

        <div class="mt-8">{{ $posts->links() }}</div>
    @endif
</div>
@endsection
