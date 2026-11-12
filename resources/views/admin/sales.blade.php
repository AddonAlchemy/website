<x-layouts.panel title="Sale-Aktionen">
    <x-page-header title="Sale-Aktionen" subtitle="Rabatte für den gesamten Shop oder einzelne Studios." />

    <div class="grid gap-6 xl:grid-cols-[24rem_minmax(0,1fr)]">
        <x-panel title="Neue Aktion" icon="percent" class="h-fit">
            <form method="POST" action="{{ route('admin.sales.store') }}" class="space-y-4" x-data="{ scope: @js(old('scope', 'shop')), bearer: @js(old('bearer', 'shop')) }">
                @csrf
                <x-input label="Name" name="name" required maxlength="80" placeholder="z. B. Summer Sale" />
                <x-input label="Rabatt in %" name="percent" inputmode="decimal" required placeholder="20" />
                <x-field label="Geltungsbereich" name="scope">
                    <select id="scope" name="scope" class="input" x-model="scope">
                        @foreach (\App\Enums\SaleScope::cases() as $option)
                            <option value="{{ $option->value }}">{{ $option->label() }}</option>
                        @endforeach
                    </select>
                </x-field>
                <x-field label="Studio" name="vendor_id" x-cloak x-show="scope === 'vendor'">
                    <select id="vendor_id" name="vendor_id" class="input">
                        <option value="">Bitte wählen</option>
                        @foreach ($vendors as $vendor)
                            <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                        @endforeach
                    </select>
                </x-field>
                <x-field label="Wer trägt den Rabatt?" name="bearer">
                    <select id="bearer" name="bearer" class="input" x-model="bearer">
                        <option value="shop">Shop trägt den Rabatt</option>
                        <option value="vendor">Studio trägt den Rabatt</option>
                        <option value="split">Aufteilung</option>
                    </select>
                </x-field>
                <div x-cloak x-show="bearer === 'split'">
                    <x-input label="Studio-Anteil in %" name="vendor_share_percent" type="number" min="0" max="100" value="50" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <x-input label="Start (optional)" name="starts_at" type="datetime-local" />
                    <x-input label="Ende (optional)" name="ends_at" type="datetime-local" />
                </div>
                <button type="submit" class="btn btn-primary w-full">Aktion erstellen</button>
            </form>
        </x-panel>

        <x-panel title="Aktionen" icon="tag" flush>
            <table class="table-grid">
                <thead><tr><th>Name</th><th>Rabatt</th><th>Bereich</th><th>Studio-Anteil</th><th>Zeitraum</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($sales as $sale)
                        <tr>
                            <td class="font-semibold">{{ $sale->name }}</td>
                            <td class="font-display font-bold text-amber-300">-{{ rtrim(rtrim(number_format($sale->percent, 2, ',', ''), '0'), ',') }} %</td>
                            <td class="text-mist">{{ $sale->vendor?->name ?? $sale->scope->label() }}</td>
                            <td class="text-mist">{{ $sale->vendor_share_percent }} %</td>
                            <td class="text-xs text-mist">{{ $sale->starts_at?->format('d.m.Y') ?? 'sofort' }} – {{ $sale->ends_at?->format('d.m.Y') ?? 'offen' }}</td>
                            <td><x-badge :tone="$sale->is_active ? 'brand' : 'slate'">{{ $sale->is_active ? 'Aktiv' : 'Pausiert' }}</x-badge></td>
                            <td class="text-right whitespace-nowrap">
                                <form method="POST" action="{{ route('admin.sales.toggle', $sale) }}" class="inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="mr-3 text-xs font-semibold text-brand hover:underline">{{ $sale->is_active ? 'Pausieren' : 'Aktivieren' }}</button>
                                </form>
                                <form method="POST" action="{{ route('admin.sales.destroy', $sale) }}" class="inline" data-confirm="Aktion löschen?">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs font-semibold text-red-400 hover:underline">Löschen</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-mist">Noch keine Sale-Aktionen.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-panel>
    </div>
</x-layouts.panel>
