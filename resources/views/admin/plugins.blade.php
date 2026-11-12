<x-layouts.panel title="Plugins verwalten">
    <x-page-header title="Plugins" subtitle="Alle Listings des Marktplatzes.">
        <form method="GET" action="{{ route('admin.plugins.index') }}" class="flex flex-wrap gap-2">
            <input type="search" name="q" value="{{ $search }}" placeholder="Name oder Produkt-ID" class="input !w-52 !py-2">
            <select name="status" class="input !w-44 !py-2" onchange="this.form.submit()">
                <option value="">Alle Status</option>
                @foreach ($statuses as $option)
                    <option value="{{ $option->value }}" @selected($status === $option)>{{ $option->label() }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">Filtern</button>
        </form>
    </x-page-header>

    <x-panel flush>
        <table class="table-grid">
            <thead><tr><th>Plugin</th><th>Studio</th><th>Status</th><th>Preis</th><th>Verkäufe</th><th>Downloads</th><th></th></tr></thead>
            <tbody>
                @forelse ($plugins as $plugin)
                    <tr>
                        <td>
                            <a href="{{ route('plugins.show', $plugin) }}" class="font-semibold hover:text-brand">{{ $plugin->name }}</a>
                            <p class="text-xs text-mist">{{ $plugin->category->name }} · <span class="font-mono">{{ $plugin->public_id }}</span></p>
                        </td>
                        <td class="text-mist">{{ $plugin->vendor->name }}</td>
                        <td>
                            <x-badge :tone="$plugin->status->tone()">{{ $plugin->status->label() }}</x-badge>
                            @if ($plugin->is_featured) <x-badge tone="brand">Empfohlen</x-badge> @endif
                        </td>
                        <td class="font-display font-bold">{{ $plugin->isFree() ? 'Gratis' : money($plugin->price_cents) }}</td>
                        <td>{{ number_format($plugin->sales_count, 0, ',', '.') }}</td>
                        <td>{{ number_format($plugin->downloads_count, 0, ',', '.') }}</td>
                        <td class="text-right whitespace-nowrap">
                            @if ($plugin->isPublished())
                                <form method="POST" action="{{ route('admin.plugins.feature', $plugin) }}" class="inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="mr-3 text-xs font-semibold text-brand hover:underline">{{ $plugin->is_featured ? 'Nicht mehr empfehlen' : 'Empfehlen' }}</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('admin.plugins.destroy', $plugin) }}" class="inline" data-confirm="{{ $plugin->name }} wirklich entfernen?">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs font-semibold text-red-400 hover:underline">Entfernen</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-mist">Keine Plugins gefunden.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-panel>
    {{ $plugins->links() }}
</x-layouts.panel>
