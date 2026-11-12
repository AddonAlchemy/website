<x-layouts.panel title="Favoriten">
    <x-page-header title="Favoriten" subtitle="Deine Merkliste – perfekt, um Plugins später zu vergleichen." />

    @if ($plugins->isEmpty())
        <x-empty icon="heart" title="Deine Merkliste ist leer" text="Tippe im Katalog auf das Herz, um Plugins zu merken."><a href="{{ route('plugins.index') }}" class="btn btn-primary btn-sm">Zum Katalog</a></x-empty>
    @else
        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($plugins as $plugin)
                <x-plugin-card :plugin="$plugin" />
            @endforeach
        </div>
        {{ $plugins->links() }}
    @endif
</x-layouts.panel>
