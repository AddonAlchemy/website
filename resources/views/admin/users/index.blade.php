<x-layouts.panel title="Benutzer">
    <x-page-header title="Benutzer" subtitle="Konten, Rollen und Lizenzen verwalten.">
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap gap-2">
            <input type="search" name="q" value="{{ $search }}" placeholder="Name oder E-Mail" class="input !w-52 !py-2">
            <select name="role" class="input !w-40 !py-2" onchange="this.form.submit()">
                <option value="">Alle Rollen</option>
                @foreach ($roles as $option)
                    <option value="{{ $option->value }}" @selected($role === $option)>{{ $option->label() }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">Filtern</button>
        </form>
    </x-page-header>

    <x-panel flush>
        <table class="table-grid">
            <thead><tr><th>Benutzer</th><th>Rolle</th><th>Studio</th><th>Lizenzen</th><th>Bestellungen</th><th>Registriert</th><th></th></tr></thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>
                            <a href="{{ route('admin.users.show', $user) }}" class="font-semibold hover:text-brand">{{ $user->name }}</a>
                            <p class="text-xs text-mist">{{ $user->email }}</p>
                        </td>
                        <td><x-badge :tone="$user->role->tone()">{{ $user->role->label() }}</x-badge></td>
                        <td class="text-mist">
                            @if ($user->vendor)
                                {{ $user->vendor->name }} <x-badge :tone="$user->vendor->status->tone()">{{ $user->vendor->status->label() }}</x-badge>
                            @else
                                –
                            @endif
                        </td>
                        <td>{{ $user->licenses_count }}</td>
                        <td>{{ $user->orders_count }}</td>
                        <td class="text-mist">{{ $user->created_at->format('d.m.Y') }}</td>
                        <td class="text-right"><a href="{{ route('admin.users.show', $user) }}" class="text-xs font-semibold text-brand hover:underline">Verwalten</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-mist">Keine Benutzer gefunden.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-panel>
    {{ $users->links() }}
</x-layouts.panel>
