<x-layouts.panel title="Bestellungen">
    <x-page-header title="Bestellungen" subtitle="Zahlungseingänge bestätigen und Lizenzen ausstellen.">
        <div class="flex flex-wrap gap-1.5">
            <a href="{{ route('admin.orders.index') }}" @class(['btn btn-sm', 'btn-primary' => $status === null, 'btn-secondary' => $status !== null])>Alle</a>
            @foreach ($statuses as $option)
                <a href="{{ route('admin.orders.index', ['status' => $option->value]) }}" @class(['btn btn-sm', 'btn-primary' => $status === $option, 'btn-secondary' => $status !== $option])>{{ $option->label() }}</a>
            @endforeach
        </div>
    </x-page-header>

    <x-panel flush>
        <table class="table-grid">
            <thead><tr><th>Bestellung</th><th>Kunde</th><th>Artikel</th><th>Zahlung</th><th>Betrag</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td>
                            <span class="font-mono text-xs font-semibold">{{ $order->number }}</span>
                            <p class="text-xs text-mist">{{ $order->created_at->format('d.m.Y H:i') }}</p>
                        </td>
                        <td><a href="{{ route('admin.users.show', $order->user) }}" class="hover:text-brand">{{ $order->user->email }}</a></td>
                        <td class="max-w-56 truncate text-mist">{{ $order->items->pluck('plugin_name')->join(', ') }}</td>
                        <td class="text-mist">{{ $order->payment_method_label }}</td>
                        <td class="font-display font-bold">{{ money($order->total_cents) }}</td>
                        <td><x-badge :tone="$order->status->tone()">{{ $order->status->label() }}</x-badge></td>
                        <td class="text-right whitespace-nowrap">
                            @if ($order->isPending())
                                <form method="POST" action="{{ route('admin.orders.fulfill', $order) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="mr-3 text-xs font-semibold text-brand hover:underline">Zahlung bestätigen</button>
                                </form>
                                <form method="POST" action="{{ route('admin.orders.cancel', $order) }}" class="inline" data-confirm="Bestellung stornieren?">
                                    @csrf
                                    <button type="submit" class="text-xs font-semibold text-red-400 hover:underline">Stornieren</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-mist">Keine Bestellungen.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-panel>
    {{ $orders->links() }}
</x-layouts.panel>
