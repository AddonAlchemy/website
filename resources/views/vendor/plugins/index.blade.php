<x-layouts.panel title="Meine Plugins">
    <x-page-header title="Meine Plugins" subtitle="Verwalte Listings, Versionen und Preise.">
        <a href="{{ route('vendor.plugins.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> Neues Plugin</a>
    </x-page-header>

    @if ($plugins->isEmpty())
        <x-empty icon="package" title="Noch keine Plugins" text="Lege dein erstes Plugin an und reiche es zur Prüfung ein.">
            <a href="{{ route('vendor.plugins.create') }}" class="btn btn-primary btn-sm">Plugin anlegen</a>
        </x-empty>
    @else
        <x-panel flush>
            <table class="table-grid">
                <thead><tr><th>Plugin</th><th>Status</th><th>Preis</th><th>Version</th><th>Verkäufe</th><th>Downloads</th><th></th></tr></thead>
                <tbody>
                    @foreach ($plugins as $plugin)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <x-plugin-icon :plugin="$plugin" size="size-10" />
                                    <div class="min-w-0">
                                        <a href="{{ route('vendor.plugins.edit', $plugin) }}" class="font-semibold hover:text-brand">{{ $plugin->name }}</a>
                                        <p class="text-xs text-mist">{{ $plugin->category->name }} · <span class="font-mono">{{ $plugin->public_id }}</span></p>
                                    </div>
                                </div>
                            </td>
                            <td><x-badge :tone="$plugin->status->tone()">{{ $plugin->status->label() }}</x-badge></td>
                            <td class="font-display font-bold">{{ $plugin->isFree() ? 'Gratis' : money($plugin->price_cents) }}</td>
                            <td class="text-mist">{{ $plugin->latest_version ? 'v'.$plugin->latest_version : '–' }} <span class="text-xs">({{ $plugin->versions_count }})</span></td>
                            <td>{{ number_format($plugin->sales_count, 0, ',', '.') }}</td>
                            <td>{{ number_format($plugin->downloads_count, 0, ',', '.') }}</td>
                            <td class="text-right"><a href="{{ route('vendor.plugins.edit', $plugin) }}" class="btn btn-secondary btn-sm"><x-icon name="pencil" class="size-4" /> Bearbeiten</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-panel>
        {{ $plugins->links() }}
    @endif
</x-layouts.panel>
