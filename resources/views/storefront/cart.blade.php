<x-layouts.app title="Warenkorb">
    <div class="container-page py-10">
        <h1 class="mb-8 text-3xl font-bold tracking-tight">Dein <span class="text-brand">Warenkorb</span></h1>

        @if ($lines->isEmpty())
            <x-empty icon="shopping-cart" title="Dein Warenkorb ist leer" text="Stöbere im Katalog und lege Plugins in den Warenkorb.">
                <a href="{{ route('plugins.index') }}" class="btn btn-primary btn-sm">Zum Katalog</a>
            </x-empty>
        @else
            <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_22rem]">
                <div class="space-y-4">
                    @foreach ($lines as $line)
                        <div class="card flex items-center gap-4 p-4">
                            <x-plugin-icon :plugin="$line->plugin" />
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('plugins.show', $line->plugin) }}" class="block truncate font-display font-semibold hover:text-brand">{{ $line->plugin->name }}</a>
                                <p class="truncate text-xs text-mist">von {{ $line->plugin->vendor->name }} · {{ $line->plugin->license_model->label() }}</p>
                                @if ($line->quote->isDiscounted())
                                    <x-badge tone="amber" class="mt-1.5">{{ $line->quote->sale->name }}</x-badge>
                                @endif
                            </div>
                            <x-price :quote="$line->quote" class="text-lg" />
                            <form method="POST" action="{{ route('cart.destroy', $line->plugin) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="rounded p-2 text-mist transition hover:text-red-400" title="Entfernen"><x-icon name="trash-2" class="size-4" /></button>
                            </form>
                        </div>
                    @endforeach

                    <form method="POST" action="{{ route('cart.clear') }}" data-confirm="Warenkorb wirklich leeren?">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-sm text-mist hover:text-red-400">Warenkorb leeren</button>
                    </form>
                </div>

                <aside class="card h-fit p-6 lg:sticky lg:top-24">
                    <h2 class="text-lg font-semibold">Zusammenfassung</h2>
                    <dl class="mt-4 space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="text-mist">Zwischensumme</dt><dd>{{ money($originalTotalCents) }}</dd></div>
                        @if ($originalTotalCents > $totalCents)
                            <div class="flex justify-between text-amber-300"><dt>Rabatt</dt><dd>-{{ money($originalTotalCents - $totalCents) }}</dd></div>
                        @endif
                        <div class="flex justify-between border-t border-line pt-3 font-display text-lg font-bold"><dt>Gesamt</dt><dd class="text-brand">{{ money($totalCents) }}</dd></div>
                    </dl>
                    <p class="mt-1 text-right text-xs text-mist">inkl. gesetzlicher MwSt.</p>

                    @auth
                        <form method="POST" action="{{ route('checkout.store') }}" class="mt-6 space-y-4">
                            @csrf
                            @if ($totalCents > 0)
                                <fieldset>
                                    <legend class="label">Zahlungsart</legend>
                                    <div class="space-y-2">
                                        @foreach ($paymentMethods as $method)
                                            <label class="flex items-center gap-3 rounded border border-line px-3 py-2.5 text-sm transition has-[:checked]:border-brand has-[:checked]:bg-brand/5">
                                                <input type="radio" name="payment_method" value="{{ $method->id }}" class="checkbox !rounded-full" @checked($loop->first)>
                                                <x-icon name="credit-card" class="size-4 text-mist" /> {{ $method->label }}
                                            </label>
                                        @endforeach
                                    </div>
                                </fieldset>
                            @endif
                            <label class="flex items-start gap-2.5 text-xs leading-relaxed text-mist">
                                <input type="checkbox" name="terms" value="1" class="checkbox mt-0.5" required>
                                <span>Ich akzeptiere die <a href="{{ route('legal.show', 'agb') }}" class="text-brand hover:underline" target="_blank">AGB</a> und stimme der sofortigen Bereitstellung digitaler Inhalte zu.</span>
                            </label>
                            <button type="submit" class="btn btn-primary w-full">{{ $totalCents > 0 ? 'Zahlungspflichtig bestellen' : 'Kostenlos freischalten' }}</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-primary mt-6 w-full">Zum Bestellen anmelden</a>
                        <p class="mt-3 text-center text-xs text-mist">Noch kein Konto? <a href="{{ route('register') }}" class="text-brand hover:underline">Jetzt registrieren</a></p>
                    @endauth
                </aside>
            </div>
        @endif
    </div>
</x-layouts.app>
