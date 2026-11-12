@php
    $vendor = $settlement->vendor;
    $items = $settlement->order->items->where('vendor_id', $vendor->id);
    $company = setting('company_name') ?: setting('brand_first').' '.setting('brand_second');
@endphp
<x-layouts.print :title="'Abrechnung '.$settlement->number">
    <header class="flex items-start justify-between gap-6 border-b border-neutral-200 pb-6">
        <div>
            <p class="font-display text-2xl font-extrabold tracking-wider">{{ setting('brand_first') }}<span style="color: {{ app(\App\Services\Settings::class)->accentColor() }}">{{ setting('brand_second') }}</span></p>
            <p class="mt-2 text-xs whitespace-pre-line text-neutral-500">{{ $company }}
{{ setting('company_address') }}</p>
        </div>
        <div class="text-right">
            <h1 class="font-display text-xl font-bold">Abrechnung</h1>
            <p class="mt-1 font-mono text-sm">{{ $settlement->number }}</p>
            <p class="text-xs text-neutral-500">Datum: {{ $settlement->created_at->format('d.m.Y') }}</p>
            <p class="text-xs text-neutral-500">Status: {{ $settlement->status->label() }}</p>
        </div>
    </header>

    <section class="mt-6 grid grid-cols-2 gap-6">
        <div>
            <p class="text-xs font-semibold tracking-wider text-neutral-500 uppercase">Studio</p>
            <p class="mt-1">{{ $vendor->name }}<br>{{ $vendor->user->email }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold tracking-wider text-neutral-500 uppercase">Bestellung</p>
            <p class="mt-1 font-mono">{{ $settlement->order->number }}</p>
            <p class="text-xs text-neutral-500">Zahlungsart: {{ $settlement->order->payment_method_label }}</p>
        </div>
    </section>

    <table class="mt-8 w-full text-left">
        <thead><tr class="border-b-2 border-neutral-900 text-xs tracking-wider uppercase"><th class="py-2">Produkt</th><th class="py-2 text-right">Listenpreis</th><th class="py-2 text-right">Rabatt</th><th class="py-2 text-right">Bezahlt</th></tr></thead>
        <tbody>
            @foreach ($items as $item)
                <tr class="border-b border-neutral-200">
                    <td class="py-2.5">{{ $item->plugin_name }} <span class="font-mono text-xs text-neutral-500">({{ $item->plugin_public_id }})</span></td>
                    <td class="py-2.5 text-right">{{ money($item->original_price_cents) }}</td>
                    <td class="py-2.5 text-right">{{ $item->discount_cents > 0 ? '-'.money($item->discount_cents) : '–' }}</td>
                    <td class="py-2.5 text-right">{{ money($item->unit_price_cents) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <dl class="mt-8 ml-auto max-w-sm space-y-1.5">
        <div class="flex justify-between"><dt>Umsatz (regulär)</dt><dd>{{ money($settlement->gross_cents) }}</dd></div>
        <div class="flex justify-between text-neutral-600"><dt>Sale-Anteil Studio</dt><dd>-{{ money($settlement->vendor_discount_cents) }}</dd></div>
        @if ($settlement->platform_discount_cents > 0)
            <div class="flex justify-between text-xs text-neutral-500"><dt>Sale-Anteil Shop (nicht abgezogen)</dt><dd>{{ money($settlement->platform_discount_cents) }}</dd></div>
        @endif
        <div class="flex justify-between text-neutral-600"><dt>Zahlungsgebühr</dt><dd>-{{ money($settlement->payment_fee_cents) }}</dd></div>
        <div class="flex justify-between text-neutral-600"><dt>Plattform-Anteil</dt><dd>-{{ money($settlement->platform_fee_cents) }}</dd></div>
        <div class="flex justify-between border-t-2 border-neutral-900 pt-2 font-display text-lg font-bold"><dt>Auszahlungsbetrag</dt><dd>{{ money($settlement->net_cents) }}</dd></div>
    </dl>

    <p class="mt-10 border-t border-neutral-200 pt-4 text-xs text-neutral-500">
        @if ($settlement->released_at) Freigegeben am {{ $settlement->released_at->format('d.m.Y') }}. @endif
        @if ($settlement->paid_out_at) Ausgezahlt am {{ $settlement->paid_out_at->format('d.m.Y') }}. @endif
        Beträge werden nach Zahlungseingang und Freigabe dem Guthaben gutgeschrieben.
    </p>
</x-layouts.print>
