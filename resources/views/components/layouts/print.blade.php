@props(['title'])
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-ink-950 py-8 print:bg-white print:py-0">
    <div class="no-print mx-auto mb-4 flex max-w-3xl items-center justify-between px-4">
        <a href="{{ url()->previous() }}" class="btn btn-secondary btn-sm"><x-icon name="arrow-left" class="size-4" /> Zurück</a>
        <button type="button" class="btn btn-primary btn-sm" onclick="window.print()"><x-icon name="printer" class="size-4" /> Drucken / PDF</button>
    </div>
    <article class="mx-auto max-w-3xl rounded-lg bg-white p-10 text-sm text-neutral-900 shadow-2xl print:rounded-none print:p-0 print:shadow-none">
        {{ $slot }}
    </article>
</body>
</html>
