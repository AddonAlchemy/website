<x-layouts.panel :title="$user->name">
    <x-page-header :title="$user->name" :subtitle="$user->email.' · registriert am '.$user->created_at->format('d.m.Y')">
        <x-badge :tone="$user->role->tone()">{{ $user->role->label() }}</x-badge>
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary btn-sm"><x-icon name="arrow-left" class="size-4" /> Zurück</a>
    </x-page-header>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="space-y-6">
            <x-panel title="Lizenzen" icon="key-round" flush>
                <x-slot:actions>
                    <form method="POST" action="{{ route('admin.users.licenses.store', $user) }}" class="flex gap-2">
                        @csrf
                        <select name="plugin_id" class="input !w-56 !py-1.5" required>
                            <option value="" disabled selected>Plugin wählen …</option>
                            @foreach ($plugins as $plugin)
                                <option value="{{ $plugin->id }}">{{ $plugin->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">Lizenz erteilen</button>
                    </form>
                </x-slot:actions>
                <table class="table-grid">
                    <thead><tr><th>Plugin</th><th>Schlüssel</th><th>Quelle</th><th>Status</th><th>Instanzen</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($licenses as $license)
                            <tr>
                                <td class="font-semibold">{{ $license->plugin->name }}</td>
                                <td class="font-mono text-xs">{{ $license->key }}</td>
                                <td class="text-mist">{{ $license->source->label() }}</td>
                                <td><x-badge :tone="$license->isExpired() ? 'amber' : $license->status->tone()">{{ $license->isExpired() ? 'Abgelaufen' : $license->status->label() }}</x-badge></td>
                                <td>{{ $license->activations_count }}</td>
                                <td class="text-right">
                                    @if ($license->isUsable())
                                        <form method="POST" action="{{ route('admin.licenses.destroy', $license) }}" data-confirm="Lizenz wirklich entziehen?">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-xs font-semibold text-red-400 hover:underline">Entziehen</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-mist">Keine Lizenzen.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-panel>

            <x-panel title="Letzte Bestellungen" icon="receipt" flush>
                <table class="table-grid">
                    <thead><tr><th>Bestellung</th><th>Datum</th><th>Betrag</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr>
                                <td class="font-mono text-xs font-semibold">{{ $order->number }}</td>
                                <td class="text-mist">{{ $order->created_at->format('d.m.Y H:i') }}</td>
                                <td class="font-display font-bold">{{ money($order->total_cents) }}</td>
                                <td><x-badge :tone="$order->status->tone()">{{ $order->status->label() }}</x-badge></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-mist">Keine Bestellungen.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-panel>
        </div>

        <div class="space-y-6">
            <x-panel title="Rolle" icon="shield">
                <form method="POST" action="{{ route('admin.users.update', $user) }}" class="flex gap-2">
                    @csrf @method('PATCH')
                    <select name="role" class="input">
                        @foreach ($roles as $option)
                            <option value="{{ $option->value }}" @selected($user->role === $option)>{{ $option->label() }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-secondary btn-sm">Speichern</button>
                </form>
                @if ($user->vendor)
                    <p class="mt-3 text-xs text-mist">Studio: {{ $user->vendor->name }} ({{ $user->vendor->status->label() }})</p>
                @endif
            </x-panel>

            <x-panel title="Passwort zurücksetzen" icon="lock">
                <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-3">
                    @csrf @method('PATCH')
                    <x-input name="password" type="password" placeholder="Neues Passwort (min. 8 Zeichen)" required minlength="8" autocomplete="new-password" />
                    <button type="submit" class="btn btn-secondary btn-sm w-full">Passwort setzen</button>
                </form>
            </x-panel>

            <x-panel title="Konto löschen" icon="triangle-alert">
                <p class="text-sm text-mist">Löscht das Konto inklusive Lizenzen, Bestellungen und Studio-Daten unwiderruflich.</p>
                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="mt-4" data-confirm="{{ $user->email }} wirklich löschen?">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm w-full">Benutzer löschen</button>
                </form>
            </x-panel>
        </div>
    </div>
</x-layouts.panel>
