@props(['value' => 0, 'count' => null, 'size' => 'size-4'])
<span {{ $attributes->class('inline-flex items-center gap-1.5') }} title="{{ number_format((float) $value, 1, ',', '.') }} von 5">
    <span class="inline-flex">
        @for ($star = 1; $star <= 5; $star++)
            <x-icon name="star" @class([$size, 'fill-amber-400 text-amber-400' => $value >= $star - 0.25, 'text-line' => $value < $star - 0.25]) />
        @endfor
    </span>
    @if ($count !== null)
        <span class="text-xs text-mist">{{ number_format((float) $value, 1, ',', '.') }} ({{ $count }})</span>
    @endif
</span>
