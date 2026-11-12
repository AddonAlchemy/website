<x-layouts.panel title="Einstellungen">
    <x-page-header title="Website-Einstellungen" subtitle="Branding, Lizenzregeln, Gebühren und Rechtstexte." />

    <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
        @csrf @method('PUT')

        <x-panel title="Branding" icon="palette">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-input label="Markenname (Teil 1)" name="brand_first" :value="$values['brand_first']" required maxlength="30" />
                <x-input label="Markenname (Teil 2, Akzentfarbe)" name="brand_second" :value="$values['brand_second']" required maxlength="30" />
                <x-input label="Slogan" name="tagline" :value="$values['tagline']" maxlength="300" wrapper-class="sm:col-span-2" />
                <x-field label="Akzentfarbe" name="accent_color">
                    <input type="color" id="accent_color" name="accent_color" value="{{ old('accent_color', $values['accent_color']) }}" class="h-11 w-full rounded border border-line bg-ink-900 p-1">
                </x-field>
                <x-input label="Währungssymbol" name="currency" :value="$values['currency']" required maxlength="5" />
            </div>
        </x-panel>

        <x-panel title="Wartungsmodus" icon="construction">
            <label class="flex items-center gap-3 text-sm">
                <input type="hidden" name="maintenance" value="0">
                <input type="checkbox" name="maintenance" value="1" class="checkbox" @checked(old('maintenance', $values['maintenance']))>
                Wartungsmodus aktivieren (nur Administratoren haben Zugriff)
            </label>
            <x-input label="Wartungshinweis" name="maintenance_message" :value="$values['maintenance_message']" maxlength="300" wrapper-class="mt-4" />
        </x-panel>

        <x-panel title="Lizenzregeln & Gebühren" icon="scale">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <x-input label="Max. IPs je Lizenz" name="max_ips" type="number" min="1" max="20" :value="$values['max_ips']" required />
                <x-input label="Produkte je Subuser-Key" name="subkey_max_plugins" type="number" min="1" max="20" :value="$values['subkey_max_plugins']" required />
                <x-input label="Subuser-Sperrfrist (Tage)" name="subkey_lock_days" type="number" min="0" max="365" :value="$values['subkey_lock_days']" required />
                <x-input :label="'Mindestpreis ('.$values['currency'].')'" name="min_price" inputmode="decimal" :value="number_format($values['min_price_cents'] / 100, 2, ',', '')" required />
                <x-input label="Plattform-Anteil (%)" name="platform_fee_percent" inputmode="decimal" :value="str_replace('.', ',', (string) $values['platform_fee_percent'])" required />
            </div>
        </x-panel>

        <x-panel title="Zahlungsarten" icon="credit-card">
            <div class="space-y-3" x-data="{ rows: @js(collect(old('payment_methods', collect($values['payment_methods'])->map(fn ($m) => ['label' => $m['label'], 'percent' => $m['percent'], 'fixed' => $m['fixed_cents'] / 100])->all()))->values()) }">
                <template x-for="(row, index) in rows" :key="index">
                    <div class="grid items-end gap-3 sm:grid-cols-[minmax(0,1fr)_8rem_8rem_auto]">
                        <div><label class="label">Bezeichnung</label><input type="text" class="input" :name="`payment_methods[${index}][label]`" x-model="row.label" maxlength="40"></div>
                        <div><label class="label">Gebühr %</label><input type="text" inputmode="decimal" class="input" :name="`payment_methods[${index}][percent]`" x-model="row.percent"></div>
                        <div><label class="label">Fix ({{ $values['currency'] }})</label><input type="text" inputmode="decimal" class="input" :name="`payment_methods[${index}][fixed]`" x-model="row.fixed"></div>
                        <button type="button" class="btn btn-danger btn-sm" x-on:click="rows.splice(index, 1)" x-bind:disabled="rows.length === 1" title="Entfernen"><x-icon name="trash-2" class="size-4" /></button>
                    </div>
                </template>
                <button type="button" class="btn btn-secondary btn-sm" x-on:click="rows.push({ label: '', percent: 0, fixed: 0 })"><x-icon name="plus" class="size-4" /> Zahlungsart hinzufügen</button>
            </div>
        </x-panel>

        <x-panel title="Firmendaten & Cookie-Banner" icon="building-2">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-input label="Firmenname" name="company_name" :value="$values['company_name']" maxlength="120" />
                <x-input label="Kontakt-E-Mail" name="company_email" type="email" :value="$values['company_email']" maxlength="120" />
                <x-input label="Anschrift" name="company_address" :value="$values['company_address']" maxlength="300" wrapper-class="sm:col-span-2" />
            </div>
            <label class="mt-5 flex items-center gap-3 text-sm">
                <input type="hidden" name="cookie_enabled" value="0">
                <input type="checkbox" name="cookie_enabled" value="1" class="checkbox" @checked(old('cookie_enabled', $values['cookie_enabled']))>
                Cookie-Banner anzeigen
            </label>
            <div class="mt-4"><x-textarea label="Cookie-Hinweis" name="cookie_text" :value="$values['cookie_text']" rows="3" maxlength="600" /></div>
        </x-panel>

        <x-panel title="Rechtstexte (Markdown)" icon="gavel">
            <div class="space-y-5" x-data="{ tab: 'legal_imprint' }">
                <div class="flex flex-wrap gap-1 border-b border-line">
                    @foreach (['legal_imprint' => 'Impressum', 'legal_privacy' => 'Datenschutz', 'legal_terms' => 'AGB', 'legal_vendor_terms' => 'Verkäufer-Bedingungen'] as $key => $label)
                        <button type="button" class="border-b-2 px-3 py-2 font-display text-sm font-semibold" :class="tab === '{{ $key }}' ? 'border-brand text-brand' : 'border-transparent text-mist'" x-on:click="tab = '{{ $key }}'">{{ $label }}</button>
                    @endforeach
                </div>
                @foreach (['legal_imprint', 'legal_privacy', 'legal_terms', 'legal_vendor_terms'] as $key)
                    <div x-cloak x-show="tab === '{{ $key }}'">
                        <textarea name="{{ $key }}" rows="16" class="input font-mono" maxlength="30000">{{ old($key, $values[$key]) }}</textarea>
                    </div>
                @endforeach
            </div>
        </x-panel>

        <div class="sticky bottom-4 flex justify-end">
            <button type="submit" class="btn btn-primary shadow-xl shadow-black/60"><x-icon name="save" class="size-4" /> Einstellungen speichern</button>
        </div>
    </form>
</x-layouts.panel>
