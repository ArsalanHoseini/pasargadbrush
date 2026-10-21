<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@hasSection('title')@yield('title') — پاسارگاد برس@else پاسارگاد برس | تولیدکننده برس و فرچه صنعتی@endif</title>
    <meta name="description" content="@yield('meta_description', 'تولیدکننده انواع برس و فرچه صنعتی — برس ابزاری، قالیشویی، هویج‌شویی، نواری و درزگیر، صنایع غذایی.')">
    <link rel="canonical" href="@yield('canonical', url()->current())">
    <meta property="og:title" content="@yield('title', 'پاسارگاد برس')">
    <meta property="og:description" content="@yield('meta_description', 'تولیدکننده انواع برس و فرچه صنعتی')">
    <meta property="og:type" content="website">
    @stack('structured-data')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-white text-neutral-900 antialiased">
    @include('partials.header')

    <main class="flex-1">
        @yield('content')
    </main>

    @include('partials.footer')
</body>
</html>
