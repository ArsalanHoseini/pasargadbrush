<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        // yieldContent() returns HTML-escaped text (defaults too); compare and print it as such.
        $siteName = e(\App\Support\SiteProfile::name());
        $pageTitle = trim(View::yieldContent('title'));
        $documentTitle = match (true) {
            $pageTitle === '' => $siteName.' | '.e(config('site.tagline')),
            str_contains($pageTitle, $siteName) => $pageTitle,
            default => $pageTitle.' — '.$siteName,
        };
        $metaDescription = trim(View::yieldContent('meta_description', \App\Support\SiteProfile::description()));
        $canonicalUrl = trim(View::yieldContent('canonical', url()->current()));
        $ogImage = trim(View::yieldContent('og_image', asset(config('site.logo'))));
    @endphp
    <title>{!! $documentTitle !!}</title>
    <meta name="description" content="{!! $metaDescription !!}">
    <link rel="canonical" href="{!! $canonicalUrl !!}">
    @hasSection('robots')
        <meta name="robots" content="@yield('robots')">
    @endif
    <meta property="og:site_name" content="{!! $siteName !!}">
    <meta property="og:locale" content="fa_IR">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="{!! $pageTitle !== '' ? $pageTitle : $documentTitle !!}">
    <meta property="og:description" content="{!! $metaDescription !!}">
    <meta property="og:url" content="{!! $canonicalUrl !!}">
    <meta property="og:image" content="{!! $ogImage !!}">
    <meta name="twitter:card" content="summary_large_image">
    @stack('meta')
    {{-- Plain-Markdown summaries of the whole site for AI assistants (llmstxt.org). --}}
    <link rel="alternate" type="text/markdown" href="{{ route('llms') }}" title="llms.txt">
    <link rel="alternate" type="text/markdown" href="{{ route('llms.full') }}" title="llms-full.txt">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    {!! app(\App\Services\Seo\SchemaGraph::class)->render(html_entity_decode($canonicalUrl), html_entity_decode($pageTitle !== '' ? $pageTitle : $documentTitle), html_entity_decode($metaDescription)) !!}
    <link rel="preload" as="font" type="font/woff2" href="{{ Vite::asset('resources/fonts/Vazirmatn-Regular.woff2') }}" crossorigin>
    <link rel="preload" as="font" type="font/woff2" href="{{ Vite::asset('resources/fonts/Vazirmatn-Bold.woff2') }}" crossorigin>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-white pb-16 text-justify text-neutral-900 antialiased md:pb-0">
    @include('partials.header')

    <main class="flex-1">
        @yield('content')
    </main>

    @include('partials.footer')
</body>
</html>
