<x-layouts.panel title="Kontrollzentrum">
    <x-page-header title="Kontrollzentrum" subtitle="Marktplatz-Kennzahlen und offene Aufgaben." />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Umsatz gesamt" :value="money($stats['revenueCents'])" icon="banknote" :hint="money($stats['platformFeeCents']).' Plattform-Anteil'" :href="route('admin.settlements.index')" />
        <x-stat label="Bestellungen" :value="number_format($stats['orders'], 0, ',', '.')" icon="shopping-bag" :hint="$stats['pendingOrders'].' offen'" :href="route('admin.orders.index')" />
        <x-stat label="Benutzer" :value="number_format($stats['users'], 0, ',', '.')" icon="users" :hint="$stats['vendors'].' aktive Vendoren'" :href="route('admin.users.index')" />
        <x-stat label="Plugins" :value="number_format($stats['plugins'], 0, ',', '.')" icon="boxes" :hint="number_format($stats['downloads'], 0, ',', '.').' Downloads'" :href="route('admin.plugins.index')" />
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Moderations-Queue" :value="$stats['pendingPlugins'] + $stats['pendingVersions']" icon="shield-check" :hint="$stats['pendingPlugins'].' Plugins · '.$stats['pendingVersions'].' Versionen'" :href="route('admin.moderation.index')" />
        <x-stat label="Vendor-Bewerbungen" :value="$stats['pendingVendors']" icon="store" :href="route('admin.vendors.index')" />
        <x-stat label="Offene Tickets" :value="$stats['openTickets']" icon="life-buoy" :href="route('admin.tickets.index')" />
        <x-stat label="Aktive Instanzen (24h)" :value="$stats['activeInstances']" icon="activity" :href="route('admin.activations.index')" />
    </div>

    <div class="mt-8 grid gap-6 xl:grid-cols-2">
        <x-panel title="Umsatz (30 Tage)" icon="chart-line">
            <div class="h-64" x-data="chart({ label: 'Umsatz', suffix: ' {{ $settings->get('currency') }}', labels: @js($stats['revenueSeries']['labels']), values: @js($stats['revenueSeries']['values']) })"><canvas x-ref="canvas"></canvas></div>
        </x-panel>
        <x-panel title="Neue Benutzer (30 Tage)" icon="user-plus">
            <div class="h-64" x-data="chart({ type: 'bar', label: 'Registrierungen', labels: @js($stats['signupSeries']['labels']), values: @js($stats['signupSeries']['values']) })"><canvas x-ref="canvas"></canvas></div>
        </x-panel>
    </div>

    <x-panel title="Bestseller" icon="trending-up" flush class="mt-6">
        <table class="table-grid">
            <thead><tr><th>Plugin</th><th>Studio</th><th>Verkäufe</th><th>Downloads</th><th>Bewertung</th></tr></thead>
            <tbody>
                @forelse ($stats['topPlugins'] as $plugin)
                    <tr>
                        <td><a href="{{ route('plugins.show', $plugin) }}" class="font-semibold hover:text-brand">{{ $plugin->name }}</a></td>
                        <td class="text-mist">{{ $plugin->vendor->name }}</td>
                        <td>{{ number_format($plugin->sales_count, 0, ',', '.') }}</td>
                        <td>{{ number_format($plugin->downloads_count, 0, ',', '.') }}</td>
                        <td><x-rating :value="$plugin->rating_average" :count="$plugin->reviews_count" /></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-mist">Noch keine veröffentlichten Plugins.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-panel>
</x-layouts.panel>
