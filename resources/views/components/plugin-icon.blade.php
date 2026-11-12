@props(['plugin', 'size' => 'size-14'])
@if ($plugin->iconUrl())
    <img src="{{ $plugin->iconUrl() }}" alt="" loading="lazy" {{ $attributes->class([$size, 'shrink-0 rounded-lg border border-line object-cover']) }}>
@else
    <span {{ $attributes->class([$size, 'flex shrink-0 items-center justify-center rounded-lg border border-line bg-ink-950 text-brand']) }}>
        <x-icon :name="$plugin->relationLoaded('category') ? $plugin->category->icon : 'package'" class="size-1/2" />
    </span>
@endif
