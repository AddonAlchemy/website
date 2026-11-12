<article {{ $attributes->class('card card-hover group relative flex min-w-0 flex-col p-5') }}>
    @if ($quote->isDiscounted())
        <span class="absolute -top-2.5 left-4 rounded bg-amber-400 px-2 py-0.5 font-display text-xs font-bold text-black">-{{ rtrim(rtrim(number_format($quote->percent(), 1, ',', ''), '0'), ',') }}%</span>
    @endif

    <button type="button" class="absolute top-4 right-4 z-10 rounded p-1 transition hover:text-red-400"
            x-data="favorite('{{ route('plugins.favorite', $plugin) }}', @js($favorited))" x-on:click.prevent="toggle()"
            :class="active ? 'text-red-400' : 'text-mist/60'" :title="active ? 'Aus Favoriten entfernen' : 'Zu Favoriten hinzufügen'">
        <x-icon name="heart" class="size-5" ::class="active && 'fill-current'" />
    </button>

    <div class="flex items-start gap-4 pr-8">
        <x-plugin-icon :plugin="$plugin" />
        <div class="min-w-0">
            <h3 class="truncate text-lg font-semibold">
                <a href="{{ route('plugins.show', $plugin) }}" class="after:absolute after:inset-0 group-hover:text-brand">{{ $plugin->name }}</a>
            </h3>
            <p class="truncate text-xs text-mist">von {{ $plugin->vendor->name }} · {{ $plugin->category->name }}</p>
        </div>
    </div>

    <p class="mt-4 line-clamp-2 flex-1 text-sm leading-relaxed text-mist">{{ $plugin->summary }}</p>

    <div class="mt-4 flex flex-wrap gap-1.5">
        @if ($plugin->is_featured)
            <x-badge tone="brand"><x-icon name="sparkles" class="size-3" /> Empfohlen</x-badge>
        @endif
        @foreach ($plugin->platforms->take(3) as $platform)
            <x-badge>{{ $platform->name }}</x-badge>
        @endforeach
        @if ($plugin->platforms->count() > 3)
            <x-badge>+{{ $plugin->platforms->count() - 3 }}</x-badge>
        @endif
    </div>

    <div class="mt-4 flex items-center justify-between border-t border-line pt-4">
        <div class="space-y-1">
            <x-rating :value="$plugin->rating_average" :count="$plugin->reviews_count" />
            <p class="flex items-center gap-1 text-xs text-mist"><x-icon name="download" class="size-3.5" /> {{ number_format($plugin->downloads_count, 0, ',', '.') }}</p>
        </div>
        <x-price :quote="$quote" class="text-lg" />
    </div>
</article>
