<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'مدیریت') — پاسارگاد برس</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-neutral-50 text-neutral-900 antialiased">
    @yield('content')
</body>
</html>
