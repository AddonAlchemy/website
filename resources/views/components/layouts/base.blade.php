@props(['title' => null, 'description' => null, 'settings', 'bodyClass' => ''])
<!DOCTYPE html>
<html lang="de" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ $description ?? $settings->get('tagline') }}">
    <meta name="theme-color" content="#121214">
    <title>{{ $title ? $title.' – ' : '' }}{{ $settings->get('brand_first') }} {{ $settings->get('brand_second') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>:root { --color-brand: {{ $settings->accentColor() }}; --color-brand-hover: {{ $settings->accentHoverColor() }}; }</style>
</head>
<body class="flex min-h-full flex-col overflow-x-clip {{ $bodyClass }}">
    {{ $slot }}
    @include('partials.flash')
</body>
</html>
