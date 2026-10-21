@extends('layouts.public')

@section('title', $page->meta_title ?? $page->title)
@section('meta_description', $page->meta_description ?? \Illuminate\Support\Str::limit(strip_tags($page->body), 150))
@section('canonical', route('pages.show', $page))

@section('content')
<div class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
    <nav class="mb-6 text-xs text-neutral-500" aria-label="مسیر صفحه">
        <a href="{{ route('home') }}" class="hover:text-neutral-800">صفحه اصلی</a>
        <span class="mx-1">/</span>
        <span class="text-neutral-700">{{ $page->title }}</span>
    </nav>

    <h1 class="mb-6 text-2xl font-bold text-neutral-900">{{ $page->title }}</h1>

    <div class="article-content text-sm leading-7 text-neutral-700">
        {!! $page->body !!}
    </div>

    @if ($page->template === 'contact')
        <div class="mt-10 grid gap-8 sm:grid-cols-2">
            <div class="rounded-lg border border-neutral-200 bg-white p-6">
                <h2 class="mb-4 text-sm font-bold text-neutral-900">اطلاعات تماس</h2>
                <ul class="space-y-3 text-sm">
                    <li>
                        <span class="block text-xs text-neutral-500">موبایل</span>
                        <a href="tel:{{ setting('contact_mobile') }}" class="font-medium text-brand-accent-dark" dir="ltr">
                            {{ setting('contact_mobile') }}
                        </a>
                    </li>
                    @if (setting('contact_landline'))
                        <li>
                            <span class="block text-xs text-neutral-500">تلفن ثابت</span>
                            <a href="tel:{{ setting('contact_landline') }}" class="font-medium text-brand-accent-dark" dir="ltr">
                                {{ setting('contact_landline') }}
                            </a>
                        </li>
                    @endif
                    <li>
                        <span class="block text-xs text-neutral-500">ایمیل</span>
                        <a href="mailto:{{ setting('contact_email') }}" class="font-medium text-brand-accent-dark" dir="ltr">
                            {{ setting('contact_email') }}
                        </a>
                    </li>
                    <li>
                        <span class="block text-xs text-neutral-500">آدرس</span>
                        <span class="font-medium">{{ setting('contact_address') }}</span>
                    </li>
                </ul>
            </div>

            <x-quote-request-form />
        </div>
    @endif
</div>
@endsection
