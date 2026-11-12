<!DOCTYPE html>
<html lang="de" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') – {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full items-center justify-center bg-[radial-gradient(circle_at_top,color-mix(in_oklab,var(--color-brand)_12%,transparent)_0%,transparent_55%)] px-4">
    <div class="max-w-lg text-center">
        <p class="font-display text-7xl font-extrabold text-brand">@yield('code')</p>
        <h1 class="mt-4 text-2xl font-bold">@yield('title')</h1>
        <div class="mx-auto my-5 h-0.5 w-20 bg-line"></div>
        <p class="leading-relaxed text-mist">@yield('message')</p>
        <div class="mt-8 flex justify-center gap-3">
            @section('actions')
                <a href="{{ url('/') }}" class="btn btn-primary">Zur Startseite</a>
                <a href="{{ url('/plugins') }}" class="btn btn-secondary">Zum Katalog</a>
            @show
        </div>
    </div>
</body>
</html>
