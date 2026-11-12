@props(['icon' => 'package', 'title', 'text' => null])
<div {{ $attributes->class('rounded-lg border border-dashed border-line px-6 py-14 text-center') }}>
    <x-icon :name="$icon" class="mx-auto size-10 text-mist/60" />
    <h3 class="mt-4 text-lg font-semibold">{{ $title }}</h3>
    @if ($text)
        <p class="mx-auto mt-1 max-w-md text-sm text-mist">{{ $text }}</p>
    @endif
    @if (trim((string) $slot) !== '')
        <div class="mt-5 flex justify-center gap-3">{{ $slot }}</div>
    @endif
</div>
