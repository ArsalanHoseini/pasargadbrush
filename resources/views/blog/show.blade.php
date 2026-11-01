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
    <p class="mb-8 text-xs text-neutral-500">
        <time datetime="{{ $post->published_at?->toIso8601String() }}">{{ persian_date($post->published_at, \App\Support\PersianDate::DATE) }}</time> · {{ $post->reading_time }} دقیقه مطالعه · {{ $post->view_count }} بازدید
    </p>

    @if ($post->featuredImage->isNotEmpty())
        <div class="mb-8 aspect-video overflow-hidden rounded-lg bg-neutral-100">
            <img src="{{ $post->featuredImage->first()->url() }}" alt="{{ $post->title }}" class="h-full w-full object-cover">
        </div>
    @endif

    <div class="article-content text-sm leading-7 text-neutral-700">
        {!! $post->body !!}
    </div>

    @if ($post->tags->isNotEmpty())
        <div class="mt-6 flex flex-wrap gap-2">
            @foreach ($post->tags as $tag)
                <a href="{{ route('blog.tag', $tag) }}"
                   class="rounded-full bg-neutral-100 px-3 py-1 text-xs text-neutral-600 hover:bg-neutral-200">#{{ $tag->name }}</a>
            @endforeach
        </div>
    @endif

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

    <div class="mt-12 border-t border-neutral-100 pt-8">
        <h2 class="mb-4 text-sm font-bold text-neutral-900">دیدگاه‌ها ({{ $post->approvedComments->count() }})</h2>

        @if ($post->approvedComments->isEmpty())
            <p class="mb-6 text-sm text-neutral-500">هنوز دیدگاهی ثبت نشده است.</p>
        @else
            <ul class="mb-8 space-y-4">
                @foreach ($post->approvedComments as $comment)
                    <li class="rounded-lg border border-neutral-200 bg-white p-4">
                        <p class="mb-1 text-xs font-medium text-neutral-800">{{ $comment->author_name }}</p>
                        <p class="mb-2 text-xs text-neutral-400"><time datetime="{{ $comment->created_at->toIso8601String() }}">{{ persian_date($comment->created_at, \App\Support\PersianDate::DATE) }}</time></p>
                        <p class="text-sm text-neutral-700">{{ $comment->body }}</p>
                    </li>
                @endforeach
            </ul>
        @endif

        @if (session('comment_sent'))
            <div class="mb-4 rounded border border-green-200 bg-green-50 p-3 text-sm text-green-700">
                دیدگاه شما ثبت شد و پس از تأیید مدیر نمایش داده خواهد شد.
            </div>
        @else
            @if ($errors->any())
                <div class="mb-4 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('blog.comments.store', $post) }}" class="space-y-3">
                @csrf

                {{-- Honeypot: hidden from real visitors via CSS, not aria-hidden (so it stays
                     out of a screen reader's form flow without relying on JS). --}}
                <div class="absolute h-0 w-0 overflow-hidden opacity-0" aria-hidden="true">
                    <label>وب‌سایت<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <input name="author_name" placeholder="نام *" value="{{ old('author_name') }}" required
                           class="rounded border border-neutral-300 px-3 py-2 text-sm">
                    <input name="author_email" type="email" placeholder="ایمیل *" value="{{ old('author_email') }}" required dir="ltr"
                           class="rounded border border-neutral-300 px-3 py-2 text-sm">
                </div>
                <textarea name="body" rows="4" placeholder="دیدگاه شما *" required
                          class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">{{ old('body') }}</textarea>

                <button type="submit" class="rounded-full bg-brand-accent px-6 py-3 text-sm font-semibold text-white hover:bg-brand-accent-dark">
                    ارسال دیدگاه
                </button>
            </form>
        @endif
    </div>
</article>
@endsection
