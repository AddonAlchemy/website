<x-layouts.panel title="API-Tokens">
    <x-page-header title="API-Tokens" subtitle="Persönliche Zugriffstoken für die REST-API (Bearer-Authentifizierung).">
        <a href="{{ route('licensing.docs') }}" class="btn btn-secondary btn-sm"><x-icon name="book-open" class="size-4" /> API-Dokumentation</a>
    </x-page-header>

    @if (session('plainTextToken'))
        <div class="mb-6 rounded border border-brand/40 bg-brand/10 p-5">
            <p class="text-sm font-semibold text-brand">Dein neuer Token – er wird nur dieses eine Mal angezeigt:</p>
            <x-copy :value="session('plainTextToken')" class="mt-3 break-all" />
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[22rem_minmax(0,1fr)]">
        <x-panel title="Neuen Token erstellen" icon="plus" class="h-fit">
            <form method="POST" action="{{ route('account.tokens.store') }}" class="space-y-4">
                @csrf
                <x-input label="Bezeichnung" name="name" required maxlength="60" placeholder="z. B. Deployment-Server" />
                <fieldset>
                    <legend class="label">Berechtigungen</legend>
                    <div class="space-y-2 text-sm">
                        @foreach ($abilities as $ability => $label)
                            <label class="flex items-center gap-2.5"><input type="checkbox" name="abilities[]" value="{{ $ability }}" class="checkbox" checked> {{ $label }} <code class="text-xs text-mist">{{ $ability }}</code></label>
                        @endforeach
                    </div>
                </fieldset>
                <button type="submit" class="btn btn-primary w-full">Token erstellen</button>
            </form>
        </x-panel>

        <x-panel title="Aktive Tokens" icon="terminal" flush>
            @if ($tokens->isEmpty())
                <p class="p-5 text-sm text-mist">Du hast noch keine API-Tokens angelegt.</p>
            @else
                <table class="table-grid">
                    <thead><tr><th>Bezeichnung</th><th>Berechtigungen</th><th>Zuletzt genutzt</th><th>Erstellt</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($tokens as $token)
                            <tr>
                                <td class="font-semibold">{{ $token->name }}</td>
                                <td><div class="flex flex-wrap gap-1">@foreach ($token->abilities as $ability)<x-badge>{{ $ability }}</x-badge>@endforeach</div></td>
                                <td class="text-mist">{{ $token->last_used_at?->diffForHumans() ?? 'Noch nie' }}</td>
                                <td class="text-mist">{{ $token->created_at->format('d.m.Y') }}</td>
                                <td class="text-right">
                                    <form method="POST" action="{{ route('account.tokens.destroy', $token->id) }}" data-confirm="Token wirklich widerrufen?">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs font-semibold text-red-400 hover:underline">Widerrufen</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-panel>
    </div>
</x-layouts.panel>
