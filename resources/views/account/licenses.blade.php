<x-layouts.panel title="Lizenzen">
    <x-page-header title="Lizenz & Aktivierung" subtitle="Schlüssel, aktive Instanzen, IP-Adressen und Subuser-Keys verwalten.">
        <form method="POST" action="{{ route('account.licenses.redeem') }}" class="flex gap-2">
            @csrf
            <input type="text" name="code" class="input !w-56 font-mono uppercase" placeholder="LIC-XXXX-XXXX-XXXX" required maxlength="32" aria-label="Lizenz-Code">
            <button type="submit" class="btn btn-primary btn-sm">Code einlösen</button>
        </form>
    </x-page-header>

    @if ($licenses->isEmpty())
        <x-empty icon="key-round" title="Noch keine Lizenzen" text="Kaufe ein Plugin oder löse einen Lizenz-Code ein, um loszulegen."><a href="{{ route('plugins.index') }}" class="btn btn-primary btn-sm">Zum Katalog</a></x-empty>
    @else
        <div class="space-y-5">
            @foreach ($licenses as $license)
                @php($ips = $license->activeIps())
                <section class="card overflow-hidden" x-data="{ open: {{ $loop->first ? 'true' : 'false' }} }">
                    <header class="flex flex-wrap items-center gap-4 p-5">
                        <x-plugin-icon :plugin="$license->plugin" size="size-12" />
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('plugins.show', $license->plugin) }}" class="font-display text-lg font-semibold hover:text-brand">{{ $license->plugin->name }}</a>
                            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-mist">
                                <x-badge :tone="$license->isExpired() ? 'amber' : $license->status->tone()">{{ $license->isExpired() ? 'Abgelaufen' : $license->status->label() }}</x-badge>
                                <span>{{ $license->source->label() }}</span>
                                <span>·</span>
                                <span>{{ $license->expires_at ? 'gültig bis '.$license->expires_at->format('d.m.Y') : 'unbegrenzt gültig' }}</span>
                                <span>·</span>
                                <span>{{ count($ips) }}/{{ $maxIps }} IPs</span>
                            </div>
                        </div>
                        @if ($license->isUsable() && $license->plugin->latestRelease)
                            <a href="{{ route('downloads.start', $license->plugin->latestRelease) }}" class="btn btn-primary btn-sm"><x-icon name="download" class="size-4" /> v{{ $license->plugin->latestRelease->version }}</a>
                        @endif
                        <button type="button" class="rounded p-2 text-mist hover:text-snow" x-on:click="open = !open" aria-label="Details"><x-icon name="chevron-down" class="transition" ::class="open && 'rotate-180'" /></button>
                    </header>

                    <div x-cloak x-show="open" x-collapse class="border-t border-line">
                        <div class="grid gap-6 p-5 lg:grid-cols-2">
                            <div>
                                <p class="label">Lizenzschlüssel</p>
                                <x-copy :value="$license->key" />
                                <p class="mt-2 text-xs text-mist">Produkt-ID: <code class="kbd">{{ $license->plugin->public_id }}</code></p>
                                <form method="POST" action="{{ route('account.licenses.rotate', $license) }}" class="mt-4" data-confirm="Neuen Schlüssel erzeugen? Alle bisherigen Aktivierungen werden zurückgesetzt.">
                                    @csrf
                                    <button type="submit" class="btn btn-secondary btn-sm"><x-icon name="refresh-cw" class="size-4" /> Schlüssel neu generieren</button>
                                </form>
                            </div>
                            <div>
                                <p class="label">Aktive IP-Adressen</p>
                                @forelse ($ips as $ip)
                                    <form method="POST" action="{{ route('account.licenses.release-ip', $license) }}" class="mb-2 flex items-center justify-between gap-3 rounded border border-line px-3 py-2">
                                        @csrf @method('DELETE')
                                        <input type="hidden" name="ip" value="{{ $ip }}">
                                        <span class="flex items-center gap-2 font-mono text-sm"><x-icon name="network" class="size-4 text-brand" /> {{ $ip }}</span>
                                        <button type="submit" class="text-xs font-semibold text-red-400 hover:underline">Freigeben</button>
                                    </form>
                                @empty
                                    <p class="text-sm text-mist">Noch keine IP-Adresse aktiviert.</p>
                                @endforelse
                            </div>
                        </div>

                        @if ($license->activations->isNotEmpty())
                            <div class="overflow-x-auto border-t border-line">
                                <table class="table-grid">
                                    <thead><tr><th>Instanz-ID</th><th>Bezeichnung</th><th>IP</th><th>Zuletzt aktiv</th><th></th></tr></thead>
                                    <tbody>
                                        @foreach ($license->activations as $activation)
                                            <tr>
                                                <td class="font-mono text-xs">{{ $activation->instance_id }}</td>
                                                <td>{{ $activation->name ?: '–' }}</td>
                                                <td class="font-mono text-xs">{{ $activation->ip }}</td>
                                                <td class="text-mist">{{ $activation->last_seen_at?->diffForHumans() ?? '–' }}</td>
                                                <td class="text-right">
                                                    <form method="POST" action="{{ route('account.activations.destroy', $activation) }}">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="text-xs font-semibold text-red-400 hover:underline">Entfernen</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </section>
            @endforeach
        </div>

        <x-panel title="Subuser-Keys" icon="users" class="mt-10">
            <p class="text-sm text-mist">Gib bis zu {{ $subkeyMaxPlugins }} lizenzierte Produkte an eine einzelne Instanz frei – unabhängig von der IP-Adresse. Nach einem Widerruf ist die Reaktivierung erst nach {{ $subkeyLockDays }} Tagen möglich.</p>

            <form method="POST" action="{{ route('account.subkeys.store') }}" class="mt-5 grid gap-4 rounded border border-line bg-ink-900 p-4 lg:grid-cols-[16rem_minmax(0,1fr)_auto]">
                @csrf
                <x-input label="Bezeichnung (optional)" name="label" maxlength="60" placeholder="z. B. Server von Max" />
                <div>
                    <span class="label">Produkte auswählen (max. {{ $subkeyMaxPlugins }})</span>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($licenses->filter->isUsable() as $license)
                            <label class="flex items-center gap-2 rounded border border-line px-3 py-2 text-sm has-[:checked]:border-brand has-[:checked]:text-brand">
                                <input type="checkbox" name="plugins[]" value="{{ $license->plugin_id }}" class="checkbox"> {{ $license->plugin->name }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="flex items-end"><button type="submit" class="btn btn-primary btn-sm">Key erstellen</button></div>
            </form>

            @if ($subkeys->isNotEmpty())
                <div class="mt-5 space-y-3">
                    @foreach ($subkeys as $subkey)
                        <div class="flex flex-wrap items-center gap-4 rounded border border-line px-4 py-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-copy :value="$subkey->key" />
                                    <x-badge :tone="$subkey->status->tone()">{{ $subkey->status->label() }}</x-badge>
                                </div>
                                <p class="mt-1.5 text-xs text-mist">
                                    {{ $subkey->label ?: 'Ohne Bezeichnung' }} · {{ $subkey->plugins->pluck('name')->join(', ') ?: 'keine Produkte' }}
                                    @if ($subkey->instance_id) · gebunden an <span class="font-mono">{{ $subkey->instance_id }}</span> @endif
                                    @if ($subkey->last_seen_at) · zuletzt aktiv {{ $subkey->last_seen_at->diffForHumans() }} @endif
                                </p>
                            </div>
                            <div class="flex gap-2">
                                @if ($subkey->isActive())
                                    <form method="POST" action="{{ route('account.subkeys.revoke', $subkey) }}" data-confirm="Zugriff sofort sperren?">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-secondary btn-sm">Widerrufen</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('account.subkeys.reactivate', $subkey) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-secondary btn-sm">Reaktivieren</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('account.subkeys.destroy', $subkey) }}" data-confirm="Subuser-Key endgültig löschen?">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Löschen</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-panel>
    @endif
</x-layouts.panel>
