@props(['title', 'subtitle' => null])
<div {{ $attributes->class('mb-8 flex flex-wrap items-end justify-between gap-4') }}>
    <div>
        <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1 text-sm text-mist">{{ $subtitle }}</p>
        @endif
    </div>
    @if (trim((string) $slot) !== '')
        <div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>
    @endif
</div>
