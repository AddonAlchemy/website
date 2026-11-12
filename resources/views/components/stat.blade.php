@props(['label', 'value', 'icon' => null, 'hint' => null, 'href' => null])
<{{ $href ? 'a' : 'div' }} @if ($href) href="{{ $href }}" @endif {{ $attributes->class(['card flex items-start gap-4 p-5', 'card-hover' => $href]) }}>
    @if ($icon)
        <span class="flex size-10 shrink-0 items-center justify-center rounded bg-brand/10 text-brand"><x-icon :name="$icon" /></span>
    @endif
    <div class="min-w-0">
        <p class="font-display text-xs font-semibold tracking-wider text-mist uppercase">{{ $label }}</p>
        <p class="mt-1 truncate font-display text-2xl font-bold">{{ $value }}</p>
        @if ($hint)
            <p class="mt-0.5 text-xs text-mist">{{ $hint }}</p>
        @endif
    </div>
</{{ $href ? 'a' : 'div' }}>
