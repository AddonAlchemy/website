<x-layouts.panel :title="'Bestellung '.$order->number">
    <x-page-header :title="'Bestellung '.$order->number" :subtitle="'Aufgegeben am '.$order->created_at->format('d.m.Y \u\m H:i').' Uhr'">
        <x-badge :tone="$order->status->tone()">{{ $order->status->label() }}</x-badge>
        @if ($order->invoice)
            <a href="{{ route('account.invoices.show', $order->invoice) }}" class="btn btn-secondary btn-sm"><x-icon name="file-text" class="size-4" /> Rechnung {{ $order->invoice->number }}</a>
        @endif
    </x-page-header>

    @if ($order->isPending())
        <div class="mb-6 flex items-center gap-3 rounded border border-amber-400/30 bg-amber-400/10 px-4 py-3 text-sm text-amber-200">
            <x-icon name="clock" class="size-4" /> Deine Zahlung wird geprüft. Nach dem Zahlungseingang werden die Lizenzen automatisch freigeschaltet.
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
        <x-panel title="Artikel" icon="package" flush>
            <table class="table-grid">
                <thead><tr><th>Produkt</th><th>Lizenz</th><th class="text-right">Preis</th></tr></thead>
                <tbody>
                    @foreach ($order->items as $item)
                        <tr>
                            <td>
                                @if ($item->plugin)
                                    <a href="{{ route('plugins.show', $item->plugin) }}" class="font-semibold hover:text-brand">{{ $item->plugin_name }}</a>
                                @else
                                    <span class="font-semibold">{{ $item->plugin_name }}</span>
                                @endif
                                <p class="font-mono text-xs text-mist">{{ $item->plugin_public_id }}</p>
                            </td>
                            <td class="text-mist">{{ $item->license_model->label() }}</td>
                            <td class="text-right">
                                <span class="font-display font-bold">{{ money($item->unit_price_cents) }}</span>
                                @if ($item->discount_cents > 0)
                                    <p class="text-xs text-mist line-through">{{ money($item->original_price_cents) }}</p>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-panel>

        <x-panel title="Zusammenfassung" icon="receipt" class="h-fit">
            <dl class="space-y-2.5 text-sm">
                <div class="flex justify-between"><dt class="text-mist">Zwischensumme</dt><dd>{{ money($order->subtotal_cents) }}</dd></div>
                @if ($order->discount_cents > 0)
                    <div class="flex justify-between text-amber-300"><dt>Rabatt</dt><dd>-{{ money($order->discount_cents) }}</dd></div>
                @endif
                <div class="flex justify-between border-t border-line pt-3 font-display text-lg font-bold"><dt>Gesamt</dt><dd class="text-brand">{{ money($order->total_cents) }}</dd></div>
                <div class="flex justify-between pt-2"><dt class="text-mist">Zahlungsart</dt><dd>{{ $order->payment_method_label }}</dd></div>
                @if ($order->fulfilled_at)
                    <div class="flex justify-between"><dt class="text-mist">Freigeschaltet</dt><dd>{{ $order->fulfilled_at->format('d.m.Y H:i') }}</dd></div>
                @endif
            </dl>
            @if ($order->fulfilled_at)
                <a href="{{ route('account.licenses.index') }}" class="btn btn-primary mt-5 w-full"><x-icon name="key-round" class="size-4" /> Zu meinen Lizenzen</a>
            @endif
        </x-panel>
    </div>
</x-layouts.panel>
