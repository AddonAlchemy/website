@php
    $billing = $invoice->billing ?? [];
    $company = setting('company_name') ?: setting('brand_first').' '.setting('brand_second');
@endphp
<x-layouts.print :title="'Rechnung '.$invoice->number">
    <header class="flex items-start justify-between gap-6 border-b border-neutral-200 pb-6">
        <div>
            <p class="font-display text-2xl font-extrabold tracking-wider">{{ setting('brand_first') }}<span style="color: {{ app(\App\Services\Settings::class)->accentColor() }}">{{ setting('brand_second') }}</span></p>
            <p class="mt-2 text-xs whitespace-pre-line text-neutral-500">{{ $company }}
{{ setting('company_address') }}
{{ setting('company_email') }}</p>
        </div>
        <div class="text-right">
            <h1 class="font-display text-xl font-bold">Rechnung</h1>
            <p class="mt-1 font-mono text-sm">{{ $invoice->number }}</p>
            <p class="text-xs text-neutral-500">Datum: {{ $invoice->issued_at->format('d.m.Y') }}</p>
            <p class="text-xs text-neutral-500">Bestellung: {{ $invoice->order->number }}</p>
        </div>
    </header>

    <section class="mt-6">
        <p class="text-xs font-semibold tracking-wider text-neutral-500 uppercase">Rechnungsempfänger</p>
        <p class="mt-1 leading-relaxed">
            {{ $billing['name'] ?? '' }}<br>
            @if (filled($billing['street'] ?? null)) {{ $billing['street'] }}<br> @endif
            @if (filled($billing['city'] ?? null)) {{ $billing['postal_code'] ?? '' }} {{ $billing['city'] }}<br> @endif
            {{ $billing['email'] ?? '' }}
        </p>
    </section>

    <table class="mt-8 w-full text-left">
        <thead>
            <tr class="border-b-2 border-neutral-900 text-xs tracking-wider uppercase">
                <th class="py-2">Position</th><th class="py-2">Lizenzmodell</th><th class="py-2 text-right">Betrag</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->order->items as $item)
                <tr class="border-b border-neutral-200">
                    <td class="py-2.5">{{ $item->plugin_name }} <span class="font-mono text-xs text-neutral-500">({{ $item->plugin_public_id }})</span></td>
                    <td class="py-2.5">{{ $item->license_model->label() }}</td>
                    <td class="py-2.5 text-right">{{ money($item->unit_price_cents) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr><td colspan="2" class="pt-4 text-right font-semibold">Gesamtbetrag (inkl. MwSt.)</td><td class="pt-4 text-right font-display text-lg font-bold">{{ money($invoice->total_cents) }}</td></tr>
            <tr><td colspan="2" class="text-right text-xs text-neutral-500">Zahlungsart</td><td class="text-right text-xs text-neutral-500">{{ $invoice->order->payment_method_label }}</td></tr>
        </tfoot>
    </table>

    <p class="mt-10 border-t border-neutral-200 pt-4 text-xs text-neutral-500">Digitale Leistung – die Lizenzen wurden unmittelbar nach Zahlungseingang bereitgestellt. Vielen Dank für deinen Einkauf.</p>
</x-layouts.print>
