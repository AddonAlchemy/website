<x-layouts.panel title="Auszahlungen">
    <x-page-header title="Auszahlungen" subtitle="Dein Guthaben und die Aufschlüsselung aller Erlöse.">
        <a href="{{ route('vendor.settlements.index') }}" class="btn btn-secondary btn-sm"><x-icon name="file-text" class="size-4" /> Abrechnungen</a>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat label="In Bearbeitung" :value="money((int) ($balances['pending'] ?? 0))" icon="clock" hint="Zahlung im Clearing" />
        <x-stat label="Im Guthaben" :value="money((int) ($balances['available'] ?? 0))" icon="wallet" hint="auszahlbar" />
        <x-stat label="Ausgezahlt" :value="money((int) ($balances['paid_out'] ?? 0))" icon="circle-check" />
    </div>

    <x-panel title="Erlös-Aufschlüsselung" icon="chart-pie" class="mt-8">
        <dl class="divide-y divide-line/60 text-sm">
            <div class="flex justify-between py-2.5"><dt class="text-mist">Verkaufte Lizenzen</dt><dd>{{ number_format((int) $totals->units, 0, ',', '.') }}</dd></div>
            <div class="flex justify-between py-2.5"><dt class="text-mist">Umsatz (regulär)</dt><dd>{{ money((int) $totals->gross) }}</dd></div>
            <div class="flex justify-between py-2.5"><dt class="text-mist">Sale-Anteil (vom Studio getragen)</dt><dd class="text-amber-300">-{{ money((int) $totals->vendor_discount) }}</dd></div>
            <div class="flex justify-between py-2.5"><dt class="text-mist">Zahlungsgebühren</dt><dd class="text-amber-300">-{{ money((int) $totals->payment_fee) }}</dd></div>
            <div class="flex justify-between py-2.5"><dt class="text-mist">Plattform-Anteil ({{ rtrim(rtrim(number_format($platformFeePercent, 2, ',', ''), '0'), ',') }} %)</dt><dd class="text-amber-300">-{{ money((int) $totals->platform_fee) }}</dd></div>
            <div class="flex justify-between py-3 font-display text-lg font-bold"><dt>Netto</dt><dd class="text-brand">{{ money((int) $totals->net) }}</dd></div>
        </dl>
    </x-panel>

    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
        <x-panel title="Verkäufe nach Produkt" icon="package" flush>
            @if ($products->isEmpty())
                <p class="p-5 text-sm text-mist">Noch keine Verkäufe.</p>
            @else
                <table class="table-grid">
                    <thead><tr><th>Produkt</th><th>Verkäufe</th><th>Listenpreis-Summe</th><th>Umsatz</th></tr></thead>
                    <tbody>
                        @foreach ($products as $product)
                            <tr>
                                <td><span class="font-semibold">{{ $product->plugin_name }}</span> <span class="font-mono text-xs text-mist">{{ $product->plugin_public_id }}</span></td>
                                <td>{{ $product->units }}</td>
                                <td class="text-mist">{{ money((int) $product->gross) }}</td>
                                <td class="font-display font-bold">{{ money((int) $product->revenue) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-panel>

        <x-panel title="Zahlungsgebühren" icon="credit-card" class="h-fit">
            <ul class="space-y-2.5 text-sm">
                @foreach ($paymentMethods as $method)
                    <li class="flex justify-between gap-3"><span>{{ $method->label }}</span><span class="text-mist">{{ rtrim(rtrim(number_format($method->percent, 2, ',', ''), '0'), ',') }} % + {{ money($method->fixedCents) }}</span></li>
                @endforeach
            </ul>
        </x-panel>
    </div>
</x-layouts.panel>
