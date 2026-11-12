<x-layouts.panel title="Monitoring">
    <x-page-header title="Aktivierungs-Monitoring" subtitle="Alle registrierten Instanzen und IP-Adressen der Lizenz-API." />

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <x-stat label="Instanzen gesamt" :value="number_format($activations->total(), 0, ',', '.')" icon="server" />
        <x-stat label="Aktiv (24h)" :value="number_format($activeToday, 0, ',', '.')" icon="activity" />
        <x-stat label="Verschiedene IPs" :value="number_format($distinctIps, 0, ',', '.')" icon="network" />
    </div>

    <x-panel flush>
        <table class="table-grid">
            <thead><tr><th>Lizenzinhaber</th><th>Produkt</th><th>Instanz-ID</th><th>Bezeichnung</th><th>IP</th><th>Zuletzt aktiv</th></tr></thead>
            <tbody>
                @forelse ($activations as $activation)
                    <tr>
                        <td><a href="{{ route('admin.users.show', $activation->license->user) }}" class="hover:text-brand">{{ $activation->license->user->email }}</a></td>
                        <td>{{ $activation->license->plugin->name }} <span class="font-mono text-xs text-mist">{{ $activation->license->plugin->public_id }}</span></td>
                        <td class="font-mono text-xs">{{ $activation->instance_id }}</td>
                        <td class="text-mist">{{ $activation->name ?: '–' }}</td>
                        <td class="font-mono text-xs">{{ $activation->ip }}</td>
                        <td class="text-mist">{{ $activation->last_seen_at?->diffForHumans() ?? '–' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-mist">Noch keine Aktivierungen registriert.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-panel>
    {{ $activations->links() }}
</x-layouts.panel>
