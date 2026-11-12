@props(['quote'])
<span {{ $attributes->class('inline-flex items-baseline gap-2 font-display font-bold') }}>
    @if ($quote->isFree())
        <span class="text-brand">Gratis</span>
    @else
        <span class="text-brand">{{ money($quote->finalCents) }}</span>
        @if ($quote->isDiscounted())
            <span class="text-[0.75em] font-medium text-mist line-through">{{ money($quote->originalCents) }}</span>
        @endif
    @endif
</span>
