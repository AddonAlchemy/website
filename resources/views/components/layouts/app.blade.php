@props(['title' => null, 'description' => null])
<x-layouts.base :title="$title" :description="$description" :settings="$settings">
    @include('partials.banner')
    @include('partials.header')

    <main class="flex-1">
        {{ $slot }}
    </main>

    @include('partials.footer')
    @include('partials.cookie')
    @include('partials.help')
</x-layouts.base>
