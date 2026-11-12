@props(['title' => null])
<x-layouts.base :title="$title" :settings="$settings">
    @include('partials.banner')
    @include('partials.header')

    <main class="container-page flex-1 py-8 lg:py-10" x-data="{ nav: false }">
        <div class="lg:grid lg:grid-cols-[15rem_minmax(0,1fr)] lg:gap-10">
            <aside>
                <button type="button" class="btn btn-secondary btn-sm mb-4 w-full lg:hidden" x-on:click="nav = !nav">
                    <x-icon name="menu" class="size-4" /> Bereichsnavigation
                </button>
                <div class="hidden lg:block" x-bind:class="nav && '!block'">
                    @include('partials.panel-nav')
                </div>
            </aside>
            <div class="min-w-0 animate-rise">
                {{ $slot }}
            </div>
        </div>
    </main>

    @include('partials.footer')
</x-layouts.base>
