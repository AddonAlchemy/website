@props(['title' => null, 'heading' => null, 'subtitle' => null])
<x-layouts.base :title="$title" :settings="$settings" body-class="bg-[radial-gradient(circle_at_top,color-mix(in_oklab,var(--color-brand)_14%,transparent)_0%,transparent_55%)]">
    <main class="flex flex-1 items-center justify-center px-4 py-12">
        <div class="w-full max-w-md animate-rise">
            <a href="{{ route('home') }}" class="mb-8 block text-center font-display text-3xl font-extrabold tracking-wider">
                {{ $settings->get('brand_first') }}<span class="text-brand">{{ $settings->get('brand_second') }}</span>
            </a>
            <div class="card p-6 shadow-2xl shadow-black/40 sm:p-8">
                @if ($heading)
                    <h1 class="text-xl font-bold">{{ $heading }}</h1>
                @endif
                @if ($subtitle)
                    <p class="mt-1 text-sm text-mist">{{ $subtitle }}</p>
                @endif
                <div @class(['mt-6' => $heading || $subtitle])>
                    {{ $slot }}
                </div>
            </div>
            @isset($footer)
                <p class="mt-6 text-center text-sm text-mist">{{ $footer }}</p>
            @endisset
        </div>
    </main>
</x-layouts.base>
