@props(['value'])
<span {{ $attributes->class('inline-flex items-center gap-2') }} x-data="clipboard(@js($value))">
    <code class="kbd select-all">{{ $value }}</code>
    <button type="button" class="text-mist transition hover:text-brand" x-on:click="copy()" title="Kopieren">
        <x-icon name="copy" class="size-4" x-show="!copied" /><x-icon name="check" class="size-4 text-brand" x-cloak x-show="copied" />
    </button>
</span>
