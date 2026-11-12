@props(['title' => null, 'icon' => null, 'flush' => false])
<section {{ $attributes->class('card overflow-hidden') }}>
    @if ($title)
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4">
            <h2 class="flex items-center gap-2 text-base font-semibold">
                @if ($icon)
                    <x-icon :name="$icon" class="size-4 text-brand" />
                @endif
                {{ $title }}
            </h2>
            @isset($actions)
                <div class="flex items-center gap-2">{{ $actions }}</div>
            @endisset
        </header>
    @endif
    <div @class(['p-5' => ! $flush, 'overflow-x-auto' => $flush])>
        {{ $slot }}
    </div>
</section>
