<footer class="mt-auto border-t border-line bg-ink-950">
    <div class="container-page py-10">
        <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <p class="font-display text-lg font-extrabold tracking-wider">{{ $settings->get('brand_first') }}<span class="text-brand">{{ $settings->get('brand_second') }}</span></p>
                <p class="mt-3 text-sm leading-relaxed text-mist">{{ $settings->get('tagline') }}</p>
            </div>
            <div>
                <h4 class="text-xs font-semibold tracking-wider text-mist uppercase">Marktplatz</h4>
                <ul class="mt-3 space-y-2 text-sm">
                    <li><a href="{{ route('plugins.index') }}" class="transition hover:text-brand">Alle Plugins</a></li>
                    <li><a href="{{ route('plugins.index', ['sort' => 'newest']) }}" class="transition hover:text-brand">Neuerscheinungen</a></li>
                    <li><a href="{{ route('plugins.index', ['price' => 'free']) }}" class="transition hover:text-brand">Kostenlose Plugins</a></li>
                    <li><a href="{{ route('vendors.index') }}" class="transition hover:text-brand">Studios</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-xs font-semibold tracking-wider text-mist uppercase">Entwickler</h4>
                <ul class="mt-3 space-y-2 text-sm">
                    <li><a href="{{ route('licensing.docs') }}" class="transition hover:text-brand">Lizenz-API</a></li>
                    <li><a href="{{ route('vendor.apply') }}" class="transition hover:text-brand">Vendor werden</a></li>
                    <li><a href="{{ route('legal.show', 'verkaeufer-bedingungen') }}" class="transition hover:text-brand">Verkäufer-Bedingungen</a></li>
                    <li><a href="{{ route('support.index') }}" class="transition hover:text-brand">Support</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-xs font-semibold tracking-wider text-mist uppercase">Rechtliches</h4>
                <ul class="mt-3 space-y-2 text-sm">
                    <li><a href="{{ route('legal.show', 'impressum') }}" class="transition hover:text-brand">Impressum</a></li>
                    <li><a href="{{ route('legal.show', 'datenschutz') }}" class="transition hover:text-brand">Datenschutz</a></li>
                    <li><a href="{{ route('legal.show', 'agb') }}" class="transition hover:text-brand">AGB</a></li>
                    @if ($settings->get('cookie_enabled'))
                        <li><button type="button" class="transition hover:text-brand" x-data x-on:click="$dispatch('cookie-settings')">Cookie-Einstellungen</button></li>
                    @endif
                </ul>
            </div>
        </div>
        <p class="mt-10 border-t border-line pt-6 text-center text-xs text-mist">
            &copy; {{ now()->year }} {{ $settings->get('company_name') ?: $settings->get('brand_first').' '.$settings->get('brand_second') }}. Alle Preise inkl. gesetzlicher MwSt.
        </p>
    </div>
</footer>
