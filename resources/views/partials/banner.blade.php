@if ($banner)
    <div x-data="dismissible('banner-{{ $banner->id }}')" x-cloak x-show="visible"
         class="relative flex flex-wrap items-center justify-center gap-x-3 gap-y-1 px-10 py-2.5 text-center text-sm text-black"
         style="background-color: {{ $banner->type->color() }}">
        <x-icon :name="$banner->type->icon()" class="size-4" />
        <strong class="font-display">{{ $banner->title }}</strong>
        <span class="opacity-90">{{ $banner->body }}</span>
        <button type="button" class="absolute top-1/2 right-3 -translate-y-1/2" x-on:click="dismiss()" aria-label="Ausblenden"><x-icon name="x" class="size-4" /></button>
    </div>
@endif
