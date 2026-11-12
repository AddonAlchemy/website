<p class="mb-4 text-sm text-mist">
    <strong class="text-snow">{{ number_format($plugins->total(), 0, ',', '.') }}</strong> {{ $plugins->total() === 1 ? 'Plugin' : 'Plugins' }} gefunden
    @if ($filters->search)
        für „<span class="text-snow">{{ $filters->search }}</span>“
    @endif
</p>

@if ($plugins->isEmpty())
    <x-empty icon="search" title="Keine Plugins gefunden" text="Passe deine Filter an oder setze sie zurück, um mehr Ergebnisse zu sehen." />
@else
    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($plugins as $plugin)
            <x-plugin-card :plugin="$plugin" />
        @endforeach
    </div>

    {{ $plugins->links() }}
@endif
