<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@hasSection('title')@yield('title') — پاسارگاد برس@else پاسارگاد برس | تولیدکننده برس و فرچه صنعتی@endif</title>
    <meta name="description" content="@yield('meta_description', 'تولیدکننده انواع برس و فرچه صنعتی — برس ابزاری، قالیشویی، هویج‌شویی، نواری و درزگیر، صنایع غذایی.')">
    <link rel="canonical" href="@yield('canonical', url()->current())">
    @hasSection('robots')
        <meta name="robots" content="@yield('robots')">
    @endif
    <meta property="og:title" content="@yield('title', 'پاسارگاد برس')">
    <meta property="og:description" content="@yield('meta_description', 'تولیدکننده انواع برس و فرچه صنعتی')">
    <meta property="og:type" content="website">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    @stack('structured-data')
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
