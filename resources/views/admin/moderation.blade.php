<x-layouts.panel title="Moderation">
    <x-page-header title="Moderations-Queue" subtitle="Eingereichte Plugins und neue Versionen prüfen und freigeben." />

    <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold"><x-icon name="package" class="size-5 text-brand" /> Neue Plugins <x-badge tone="amber">{{ $plugins->count() }}</x-badge></h2>
    @forelse ($plugins as $plugin)
        <article class="card mb-5 p-5" x-data="{ rejecting: false }">
            <div class="flex flex-wrap items-start gap-4">
                <x-plugin-icon :plugin="$plugin" />
                <div class="min-w-0 flex-1">
                    <h3 class="text-lg font-semibold">{{ $plugin->name }}</h3>
                    <p class="text-xs text-mist">von {{ $plugin->vendor->name }} · {{ $plugin->category->name }} · {{ $plugin->isFree() ? 'Gratis' : money($plugin->price_cents) }} · {{ $plugin->license_model->label() }}</p>
                    <p class="mt-2 text-sm text-mist">{{ $plugin->summary }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('plugins.show', $plugin) }}" target="_blank" class="btn btn-secondary btn-sm"><x-icon name="eye" class="size-4" /> Vorschau</a>
                    <form method="POST" action="{{ route('admin.moderation.plugins.approve', $plugin) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm"><x-icon name="check" class="size-4" /> Freigeben</button>
                    </form>
                    <button type="button" class="btn btn-danger btn-sm" x-on:click="rejecting = !rejecting"><x-icon name="x" class="size-4" /> Ablehnen</button>
                </div>
            </div>

            <div class="mt-4 space-y-2 border-t border-line pt-4">
                @forelse ($plugin->versions as $version)
                    <div class="flex flex-wrap items-center gap-3 text-sm">
                        <x-badge>v{{ $version->version }}</x-badge>
                        <span class="text-mist">{{ $version->file_name }} · {{ $version->humanFileSize() }}</span>
                        <span class="text-xs text-mist">{{ $version->platforms->pluck('name')->join(', ') }}</span>
                        <a href="{{ route('downloads.start', $version) }}" class="ml-auto inline-flex items-center gap-1 text-xs font-semibold text-brand hover:underline"><x-icon name="download" class="size-4" /> Archiv prüfen</a>
                        <code class="w-full font-mono text-[0.7rem] break-all text-mist/60">SHA-256 {{ $version->file_hash }}</code>
                    </div>
                @empty
                    <p class="text-sm text-red-300">Keine Version hochgeladen.</p>
                @endforelse
            </div>

            <form x-cloak x-show="rejecting" method="POST" action="{{ route('admin.moderation.plugins.reject', $plugin) }}" class="mt-4 flex gap-2 border-t border-line pt-4">
                @csrf
                <input type="text" name="reason" class="input" placeholder="Begründung für den Vendor …" required minlength="5" maxlength="255">
                <button type="submit" class="btn btn-danger btn-sm">Ablehnung senden</button>
            </form>
        </article>
    @empty
        <x-empty icon="circle-check" title="Keine Plugins in der Queue" class="mb-8" />
    @endforelse

    <h2 class="mt-10 mb-4 flex items-center gap-2 text-lg font-semibold"><x-icon name="file-archive" class="size-5 text-brand" /> Neue Versionen <x-badge tone="amber">{{ $versions->count() }}</x-badge></h2>
    @forelse ($versions as $version)
        <article class="card mb-4 p-5" x-data="{ rejecting: false }">
            <div class="flex flex-wrap items-center gap-4">
                <div class="min-w-0 flex-1">
                    <h3 class="font-semibold">{{ $version->plugin->name }} <span class="text-brand">v{{ $version->version }}</span></h3>
                    <p class="text-xs text-mist">von {{ $version->plugin->vendor->name }} · aktuell v{{ $version->plugin->latest_version ?? '–' }} · {{ $version->file_name }} · {{ $version->humanFileSize() }} · {{ $version->platforms->pluck('name')->join(', ') }}</p>
                    <code class="font-mono text-[0.7rem] break-all text-mist/60">SHA-256 {{ $version->file_hash }}</code>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('downloads.start', $version) }}" class="btn btn-secondary btn-sm"><x-icon name="download" class="size-4" /> Archiv</a>
                    <form method="POST" action="{{ route('admin.moderation.versions.approve', $version) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm"><x-icon name="check" class="size-4" /> Freigeben</button>
                    </form>
                    <button type="button" class="btn btn-danger btn-sm" x-on:click="rejecting = !rejecting"><x-icon name="x" class="size-4" /> Ablehnen</button>
                </div>
            </div>
            @if (filled($version->changelog))
                <x-markdown :content="$version->changelog" class="mt-4 border-t border-line pt-4 text-sm" />
            @endif
            <form x-cloak x-show="rejecting" method="POST" action="{{ route('admin.moderation.versions.reject', $version) }}" class="mt-4 flex gap-2 border-t border-line pt-4">
                @csrf
                <input type="text" name="reason" class="input" placeholder="Begründung für den Vendor …" required minlength="5" maxlength="255">
                <button type="submit" class="btn btn-danger btn-sm">Ablehnung senden</button>
            </form>
        </article>
    @empty
        <x-empty icon="circle-check" title="Keine Versionen in der Queue" />
    @endforelse
</x-layouts.panel>
