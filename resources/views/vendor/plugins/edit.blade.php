<x-layouts.panel :title="$plugin->name">
    <x-page-header :title="$plugin->name" :subtitle="'Produkt-ID '.$plugin->public_id">
        <x-badge :tone="$plugin->status->tone()">{{ $plugin->status->label() }}</x-badge>
        <a href="{{ route('plugins.show', $plugin) }}" class="btn btn-secondary btn-sm"><x-icon name="eye" class="size-4" /> Vorschau</a>
        @if ($plugin->status->isEditableSubmission())
            <form method="POST" action="{{ route('vendor.plugins.submit', $plugin) }}">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm"><x-icon name="send" class="size-4" /> Zur Prüfung einreichen</button>
            </form>
        @endif
    </x-page-header>

    @if ($plugin->rejection_reason)
        <div class="mb-6 rounded border border-red-400/30 bg-red-400/10 px-4 py-3 text-sm text-red-200">
            <p class="font-semibold">Ablehnungsgrund der Moderation</p>
            <p class="mt-1">{{ $plugin->rejection_reason }}</p>
        </div>
    @elseif ($plugin->status === \App\Enums\PluginStatus::Pending)
        <div class="mb-6 flex items-center gap-3 rounded border border-amber-400/30 bg-amber-400/10 px-4 py-3 text-sm text-amber-200">
            <x-icon name="clock" class="size-4" /> Dieses Plugin wartet auf die Freigabe durch die Moderation.
        </div>
    @endif

    <div class="space-y-6">
        <x-panel title="Listing" icon="package">
            <form method="POST" action="{{ route('vendor.plugins.update', $plugin) }}" enctype="multipart/form-data">
                @csrf @method('PUT')
                @include('vendor.plugins._form')
                <div class="mt-6"><button type="submit" class="btn btn-primary">Änderungen speichern</button></div>
            </form>
        </x-panel>

        <x-panel title="Versionen" icon="file-archive" id="versions" class="scroll-mt-24">
            <form method="POST" action="{{ route('vendor.versions.store', $plugin) }}" enctype="multipart/form-data" class="grid gap-4 rounded border border-line bg-ink-900 p-4 sm:grid-cols-2">
                @csrf
                <x-input label="Versionsnummer" name="version" required maxlength="40" placeholder="1.2.0" />
                <x-input label="Unterstützte Versionen (kommagetrennt)" name="game_versions" maxlength="300" placeholder="1.20, 1.21" />
                <fieldset class="sm:col-span-2">
                    <legend class="label">Kompatible Plattformen</legend>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($platforms as $platform)
                            <label class="flex items-center gap-2 rounded border border-line px-3 py-1.5 text-sm has-[:checked]:border-brand has-[:checked]:text-brand">
                                <input type="checkbox" name="platforms[]" value="{{ $platform->id }}" class="checkbox" @checked(in_array($platform->id, array_map('intval', old('platforms', [])), true))> {{ $platform->name }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                <div class="sm:col-span-2">
                    <x-textarea label="Changelog (Markdown)" name="changelog" rows="4" maxlength="10000" />
                </div>
                <div class="sm:col-span-2">
                    <label for="archive" class="label">Release-Archiv (.{{ implode(', .', config('marketplace.releases.extensions')) }} · max. {{ (int) (config('marketplace.releases.max_size_kb') / 1024) }} MB)</label>
                    <input type="file" id="archive" name="archive" required accept=".{{ implode(',.', config('marketplace.releases.extensions')) }}" class="input file:mr-4 file:rounded file:border-0 file:bg-line file:px-3 file:py-1.5 file:text-sm file:text-snow">
                    <p class="mt-1.5 text-xs text-mist">Archive werden vor der Speicherung auf Integrität, Pfadangaben und Kompressionsrate geprüft und privat abgelegt.</p>
                    @error('archive') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2"><button type="submit" class="btn btn-primary btn-sm"><x-icon name="upload" class="size-4" /> Version hochladen</button></div>
            </form>

            @if ($plugin->versions->isNotEmpty())
                <div class="mt-5 overflow-x-auto">
                    <table class="table-grid">
                        <thead><tr><th>Version</th><th>Status</th><th>Plattformen</th><th>Datei</th><th>Downloads</th><th>Hochgeladen</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($plugin->versions as $version)
                                <tr>
                                    <td class="font-display font-bold">v{{ $version->version }}</td>
                                    <td>
                                        <x-badge :tone="$version->status->tone()">{{ $version->status->label() }}</x-badge>
                                        @if ($version->rejection_reason)
                                            <p class="mt-1 max-w-52 text-xs text-red-300">{{ $version->rejection_reason }}</p>
                                        @endif
                                    </td>
                                    <td class="text-xs text-mist">{{ $version->platforms->pluck('name')->join(', ') ?: '–' }}</td>
                                    <td class="text-xs text-mist">{{ $version->file_name }} ({{ $version->humanFileSize() }})</td>
                                    <td>{{ number_format($version->downloads_count, 0, ',', '.') }}</td>
                                    <td class="text-mist">{{ $version->created_at->format('d.m.Y') }}</td>
                                    <td class="text-right whitespace-nowrap">
                                        <a href="{{ route('downloads.start', $version) }}" class="mr-3 text-xs font-semibold text-brand hover:underline">Download</a>
                                        <form method="POST" action="{{ route('vendor.versions.destroy', $version) }}" class="inline" data-confirm="Version v{{ $version->version }} wirklich entfernen?">
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
        </x-panel>

        <x-panel title="Galerie" icon="image" id="gallery" class="scroll-mt-24">
            <form method="POST" action="{{ route('vendor.screenshots.store', $plugin) }}" enctype="multipart/form-data" class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_16rem_auto]">
                @csrf
                <div>
                    <label for="images" class="label">Screenshots (mehrere möglich)</label>
                    <input type="file" id="images" name="images[]" multiple required accept="image/png,image/jpeg,image/webp" class="input file:mr-4 file:rounded file:border-0 file:bg-line file:px-3 file:py-1.5 file:text-sm file:text-snow">
                    @error('images.*') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>
                <x-input label="Bildunterschrift (optional)" name="caption" maxlength="120" />
                <div class="flex items-end"><button type="submit" class="btn btn-secondary btn-sm"><x-icon name="upload" class="size-4" /> Hochladen</button></div>
            </form>

            @if ($plugin->screenshots->isNotEmpty())
                <div class="mt-5 grid grid-cols-2 gap-4 md:grid-cols-4">
                    @foreach ($plugin->screenshots as $screenshot)
                        <figure class="group relative overflow-hidden rounded-lg border border-line">
                            <img src="{{ $screenshot->url() }}" alt="{{ $screenshot->caption }}" loading="lazy" class="aspect-video w-full object-cover">
                            <form method="POST" action="{{ route('vendor.screenshots.destroy', $screenshot) }}" class="absolute top-2 right-2">
                                @csrf @method('DELETE')
                                <button type="submit" class="rounded bg-black/70 p-1.5 text-red-300 hover:bg-red-500 hover:text-white" title="Entfernen"><x-icon name="trash-2" class="size-4" /></button>
                            </form>
                            @if ($screenshot->caption)
                                <figcaption class="truncate bg-ink-950 px-2 py-1.5 text-xs text-mist">{{ $screenshot->caption }}</figcaption>
                            @endif
                        </figure>
                    @endforeach
                </div>
            @endif
        </x-panel>

        <x-panel title="Lizenz-Codes" icon="ticket" id="codes" class="scroll-mt-24">
            <p class="text-sm text-mist">Erzeuge einlösbare Codes, z. B. für Gewinnspiele, Partner oder Direktverkäufe. Jeder Code schaltet eine Lizenz für dieses Plugin frei.</p>
            <form method="POST" action="{{ route('vendor.codes.store', $plugin) }}" class="mt-4 grid gap-4 sm:grid-cols-[8rem_minmax(0,1fr)_auto]">
                @csrf
                <x-input label="Anzahl" name="quantity" type="number" min="1" max="50" value="1" required />
                <x-input label="Notiz (optional)" name="note" maxlength="60" />
                <div class="flex items-end"><button type="submit" class="btn btn-secondary btn-sm">Codes erzeugen</button></div>
            </form>

            @if ($codes->isNotEmpty())
                <div class="mt-5 overflow-x-auto">
                    <table class="table-grid">
                        <thead><tr><th>Code</th><th>Status</th><th>Notiz</th><th>Eingelöst von</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($codes as $code)
                                <tr>
                                    <td><x-copy :value="$code->code" /></td>
                                    <td><x-badge :tone="$code->status->tone()">{{ $code->status->label() }}</x-badge></td>
                                    <td class="text-mist">{{ $code->note ?: '–' }}</td>
                                    <td class="text-mist">{{ $code->redeemer?->email ?? '–' }}</td>
                                    <td class="text-right whitespace-nowrap">
                                        @if ($code->status !== \App\Enums\LicenseCodeStatus::Revoked)
                                            <form method="POST" action="{{ route('vendor.codes.revoke', $code) }}" class="inline" data-confirm="Code widerrufen? Eine bereits eingelöste Lizenz wird entzogen.">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="mr-3 text-xs font-semibold text-amber-300 hover:underline">Widerrufen</button>
                                            </form>
                                        @endif
                                        @if ($code->status !== \App\Enums\LicenseCodeStatus::Redeemed)
                                            <form method="POST" action="{{ route('vendor.codes.destroy', $code) }}" class="inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-xs font-semibold text-red-400 hover:underline">Löschen</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-panel>

        <x-panel title="Gefahrenzone" icon="triangle-alert">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <p class="max-w-xl text-sm text-mist">Plugins ohne Kunden werden vollständig gelöscht. Sobald Lizenzen existieren, wird das Plugin archiviert: Es verschwindet aus dem Katalog, bestehende Kunden behalten Zugriff.</p>
                <form method="POST" action="{{ route('vendor.plugins.destroy', $plugin) }}" data-confirm="Plugin wirklich entfernen?">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm"><x-icon name="trash-2" class="size-4" /> Plugin entfernen</button>
                </form>
            </div>
        </x-panel>
    </div>
</x-layouts.panel>
