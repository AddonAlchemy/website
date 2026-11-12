<x-layouts.panel title="Abrechnungen">
    <x-page-header title="Abrechnungen" subtitle="Jede erfüllte Bestellung erzeugt eine Abrechnung für dein Studio." />

    @if ($settlements->isEmpty())
        <x-empty icon="file-text" title="Noch keine Abrechnungen" text="Nach dem ersten Verkauf erscheinen hier deine Abrechnungen." />
    @else
        <x-panel flush>
            <table class="table-grid">
                <thead><tr><th>Nummer</th><th>Datum</th><th>Bestellung</th><th>Lizenzen</th><th>Umsatz</th><th>Netto</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach ($settlements as $settlement)
                        <tr>
                            <td><a href="{{ route('vendor.settlements.show', $settlement) }}" class="font-mono text-xs font-semibold hover:text-brand">{{ $settlement->number }}</a></td>
                            <td class="text-mist">{{ $settlement->created_at->format('d.m.Y') }}</td>
                            <td class="font-mono text-xs text-mist">{{ $settlement->order->number }}</td>
                            <td>{{ $settlement->units }}</td>
                            <td class="text-mist">{{ money($settlement->gross_cents) }}</td>
                            <td class="font-display font-bold">{{ money($settlement->net_cents) }}</td>
                            <td><x-badge :tone="$settlement->status->tone()">{{ $settlement->status->label() }}</x-badge></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-panel>
        {{ $settlements->links() }}
    @endif
</x-layouts.panel>
