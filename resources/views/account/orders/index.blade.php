<x-layouts.panel title="Bestellungen">
    <x-page-header title="Bestellungen & Rechnungen" subtitle="Deine komplette Kaufhistorie." />

    @if ($orders->isEmpty())
        <x-empty icon="receipt" title="Noch keine Bestellungen"><a href="{{ route('plugins.index') }}" class="btn btn-primary btn-sm">Zum Katalog</a></x-empty>
    @else
        <x-panel flush>
            <table class="table-grid">
                <thead><tr><th>Bestellung</th><th>Datum</th><th>Artikel</th><th>Betrag</th><th>Status</th><th class="text-right">Rechnung</th></tr></thead>
                <tbody>
                    @foreach ($orders as $order)
                        <tr>
                            <td><a href="{{ route('account.orders.show', $order) }}" class="font-mono font-semibold hover:text-brand">{{ $order->number }}</a></td>
                            <td class="text-mist">{{ $order->created_at->format('d.m.Y') }}</td>
                            <td class="max-w-64 truncate text-mist">{{ $order->items->pluck('plugin_name')->join(', ') }}</td>
                            <td class="font-display font-bold">{{ money($order->total_cents) }}</td>
                            <td><x-badge :tone="$order->status->tone()">{{ $order->status->label() }}</x-badge></td>
                            <td class="text-right">
                                @if ($order->invoice)
                                    <a href="{{ route('account.invoices.show', $order->invoice) }}" class="inline-flex items-center gap-1 text-xs font-semibold text-brand hover:underline"><x-icon name="file-text" class="size-4" /> {{ $order->invoice->number }}</a>
                                @else
                                    <span class="text-xs text-mist">–</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-panel>
        {{ $orders->links() }}
    @endif
</x-layouts.panel>
