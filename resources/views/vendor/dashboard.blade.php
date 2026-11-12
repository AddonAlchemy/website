<x-layouts.panel title="Vendor-Dashboard">
    <x-page-header :title="$vendor->name" subtitle="Verkäufe, Downloads und offene Prüfungen deines Studios.">
        <x-badge :tone="$vendor->status->tone()">{{ $vendor->status->label() }}</x-badge>
        @if ($vendor->isApproved())
            <a href="{{ route('vendor.plugins.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> Neues Plugin</a>
        @endif
    </x-page-header>

    @unless ($vendor->isApproved())
        <div class="mb-6 rounded border border-amber-400/30 bg-amber-400/10 px-4 py-3 text-sm text-amber-200">
            <p class="flex items-center gap-2 font-semibold"><x-icon name="clock" class="size-4" /> Dein Vendor-Account ist noch nicht freigeschaltet.</p>
            <p class="mt-1 text-amber-200/80">{{ $vendor->rejection_reason ?: 'Sobald unser Team dein Studio geprüft hat, kannst du Plugins einreichen.' }}</p>
        </div>
    @endunless

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Im Guthaben" :value="money($stats['availableCents'])" icon="wallet" :hint="money($stats['pendingCents']).' in Bearbeitung'" :href="route('vendor.payouts')" />
        <x-stat label="Verkäufe" :value="number_format($stats['sales'], 0, ',', '.')" icon="shopping-bag" :hint="money($stats['paidOutCents']).' ausgezahlt'" />
        <x-stat label="Downloads" :value="number_format($stats['downloads'], 0, ',', '.')" icon="download" />
        <x-stat label="Plugins" :value="$stats['published'].' / '.$stats['plugins']" icon="package" :hint="$stats['pendingVersions'].' Version(en) in Prüfung'" :href="route('vendor.plugins.index')" />
    </div>

    <div class="mt-8 grid gap-6 xl:grid-cols-2">
        <x-panel title="Downloads (30 Tage)" icon="chart-column">
            <div class="h-64" x-data="chart({ type: 'bar', label: 'Downloads', labels: @js($stats['downloadSeries']['labels']), values: @js($stats['downloadSeries']['values']) })"><canvas x-ref="canvas"></canvas></div>
        </x-panel>
        <x-panel title="Netto-Erlöse (30 Tage)" icon="chart-line">
            <div class="h-64" x-data="chart({ label: 'Netto', suffix: ' {{ $settings->get('currency') }}', labels: @js($stats['revenueSeries']['labels']), values: @js($stats['revenueSeries']['values']) })"><canvas x-ref="canvas"></canvas></div>
        </x-panel>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <x-panel title="Top-Plugins" icon="trending-up" flush>
            @if ($stats['topPlugins']->isEmpty())
                <p class="p-5 text-sm text-mist">Noch keine Plugins angelegt.</p>
            @else
                <table class="table-grid">
                    <thead><tr><th>Plugin</th><th>Verkäufe</th><th>Downloads</th><th>Bewertung</th></tr></thead>
                    <tbody>
                        @foreach ($stats['topPlugins'] as $plugin)
                            <tr>
                                <td><a href="{{ route('vendor.plugins.edit', $plugin) }}" class="font-semibold hover:text-brand">{{ $plugin->name }}</a></td>
                                <td>{{ number_format($plugin->sales_count, 0, ',', '.') }}</td>
                                <td>{{ number_format($plugin->downloads_count, 0, ',', '.') }}</td>
                                <td><x-rating :value="$plugin->rating_average" :count="$plugin->reviews_count" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-panel>

        <x-panel title="Letzte Abrechnungen" icon="receipt" flush>
            <x-slot:actions><a href="{{ route('vendor.settlements.index') }}" class="text-xs font-semibold text-brand hover:underline">Alle anzeigen</a></x-slot:actions>
            @forelse ($recentSettlements as $settlement)
                <a href="{{ route('vendor.settlements.show', $settlement) }}" class="flex items-center gap-4 border-b border-line/60 px-5 py-3.5 transition last:border-b-0 hover:bg-ink-700/40">
                    <div class="min-w-0 flex-1">
                        <p class="font-mono text-xs font-semibold">{{ $settlement->number }}</p>
                        <p class="text-xs text-mist">{{ $settlement->created_at->format('d.m.Y') }} · Bestellung {{ $settlement->order->number }}</p>
                    </div>
                    <span class="font-display font-bold">{{ money($settlement->net_cents) }}</span>
                    <x-badge :tone="$settlement->status->tone()">{{ $settlement->status->label() }}</x-badge>
                </a>
            @empty
                <p class="p-5 text-sm text-mist">Noch keine Verkäufe abgerechnet.</p>
            @endforelse
        </x-panel>
    </div>
</x-layouts.panel>
