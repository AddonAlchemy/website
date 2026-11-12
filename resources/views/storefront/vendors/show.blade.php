<x-layouts.app :title="$vendor->name" :description="$vendor->tagline">
    <div class="container-page py-10">
        <div class="card mb-10 flex flex-wrap items-start gap-6 p-7">
            <span class="flex size-20 shrink-0 items-center justify-center rounded-xl bg-brand/10 font-display text-4xl font-extrabold text-brand">{{ mb_strtoupper(mb_substr($vendor->name, 0, 1)) }}</span>
            <div class="min-w-0 flex-1">
                <h1 class="flex items-center gap-2 text-3xl font-bold tracking-tight">{{ $vendor->name }} <x-icon name="badge-check" class="size-6 text-brand" /></h1>
                @if ($vendor->tagline)
                    <p class="mt-1 text-mist">{{ $vendor->tagline }}</p>
                @endif
                <div class="mt-4 flex flex-wrap gap-4 text-sm text-mist">
                    <span class="flex items-center gap-1.5"><x-icon name="calendar" class="size-4" /> Dabei seit {{ ($vendor->approved_at ?? $vendor->created_at)->translatedFormat('F Y') }}</span>
                    @if ($vendor->website)
                        <a href="{{ $vendor->website }}" target="_blank" rel="noopener nofollow" class="flex items-center gap-1.5 hover:text-brand"><x-icon name="globe" class="size-4" /> Website</a>
                    @endif
                    @if ($vendor->support_email)
                        <a href="mailto:{{ $vendor->support_email }}" class="flex items-center gap-1.5 hover:text-brand"><x-icon name="mail" class="size-4" /> {{ $vendor->support_email }}</a>
                    @endif
                </div>
                @if ($vendor->bio)
                    <x-markdown :content="$vendor->bio" class="mt-5 text-sm" />
                @endif
            </div>
        </div>

        <h2 class="section-title mb-6">Plugins von {{ $vendor->name }}</h2>
        @if ($plugins->isEmpty())
            <x-empty title="Noch keine veröffentlichten Plugins" />
        @else
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($plugins as $plugin)
                    <x-plugin-card :plugin="$plugin" />
                @endforeach
            </div>
            {{ $plugins->links() }}
        @endif
    </div>
</x-layouts.app>
