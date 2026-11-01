{{--
    Standalone error page (no site layout): for unmatched URLs there is no
    session or route context, so the full layout's header data can't be built.
--}}
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') — {{ setting('site_name', 'پاسارگاد برس') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen items-center justify-center bg-neutral-50 px-4 text-neutral-900">
    <main class="max-w-md text-center">
        <a href="{{ url('/') }}" class="mb-8 inline-block">
            <img src="{{ asset('images/logo.png') }}" alt="{{ setting('site_name', 'پاسارگاد برس') }}" class="mx-auto h-14 w-auto">
        </a>
        <p class="text-5xl font-bold text-brand-accent" dir="ltr">@yield('code')</p>
        <h1 class="mt-4 text-xl font-bold">@yield('title')</h1>
        <p class="mt-3 text-sm leading-7 text-neutral-600">@yield('message')</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3 text-sm">
            <a href="{{ url('/') }}" class="rounded-full bg-brand-accent px-6 py-2.5 font-semibold text-white hover:bg-brand-accent-dark">صفحه اصلی</a>
            <a href="{{ url('/search') }}" class="rounded-full border border-neutral-300 px-6 py-2.5 font-semibold hover:border-neutral-500">جستجوی محصول</a>
        </div>
    </main>
</body>
</html>
