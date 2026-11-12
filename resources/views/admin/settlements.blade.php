<x-layouts.panel title="Abrechnungen">
    <x-page-header title="Vendor-Abrechnungen" subtitle="Erlöse freigeben und Auszahlungen dokumentieren.">
        <div class="flex flex-wrap gap-1.5">
            <a href="{{ route('admin.settlements.index') }}" @class(['btn btn-sm', 'btn-primary' => $status === null, 'btn-secondary' => $status !== null])>Alle</a>
            @foreach ($statuses as $option)
                <a href="{{ route('admin.settlements.index', ['status' => $option->value]) }}" @class(['btn btn-sm', 'btn-primary' => $status === $option, 'btn-secondary' => $status !== $option])>{{ $option->label() }}</a>
            @endforeach
        </div>
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($statuses as $option)
            <x-stat :label="$option->label()" :value="money((int) ($totals[$option->value]->net ?? 0))" :hint="money((int) ($totals[$option->value]->fees ?? 0)).' Plattform-Anteil'" />
        @endforeach
    </div>

    <x-panel flush>
        <table class="table-grid">
            <thead><tr><th>Nummer</th><th>Studio</th><th>Bestellung</th><th>Umsatz</th><th>Gebühren</th><th>Netto</th><th>Status</th></tr></thead>
            <tbody>
                @forelse ($settlements as $settlement)
                    <tr>
                        <td>
                            <span class="font-mono text-xs font-semibold">{{ $settlement->number }}</span>
                            <p class="text-xs text-mist">{{ $settlement->created_at->format('d.m.Y') }}</p>
                        </td>
                        <td>{{ $settlement->vendor->name }}</td>
                        <td class="font-mono text-xs text-mist">{{ $settlement->order->number }}</td>
                        <td class="text-mist">{{ money($settlement->gross_cents) }}</td>
                        <td class="text-xs text-mist">Zahlung {{ money($settlement->payment_fee_cents) }}<br>Plattform {{ money($settlement->platform_fee_cents) }}</td>
                        <td class="font-display font-bold">{{ money($settlement->net_cents) }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.settlements.update', $settlement) }}">
                                @csrf @method('PATCH')
                                <select name="status" class="input !w-40 !py-1.5" onchange="this.form.submit()" aria-label="Status">
                                    @foreach ($statuses as $option)
                                        <option value="{{ $option->value }}" @selected($settlement->status === $option)>{{ $option->label() }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-mist">Keine Abrechnungen.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-panel>
    {{ $settlements->links() }}
</x-layouts.panel>
