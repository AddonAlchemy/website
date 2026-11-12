<x-layouts.panel title="Tickets">
    <x-page-header title="Support-Tickets" subtitle="Plattform-Anfragen und Übersicht aller Studio-Tickets." />

    <x-panel flush>
        <table class="table-grid">
            <thead><tr><th>Ticket</th><th>Betreff</th><th>Von</th><th>Empfänger</th><th>Status</th><th>Aktualisiert</th></tr></thead>
            <tbody>
                @forelse ($tickets as $ticket)
                    <tr>
                        <td><a href="{{ route('support.show', $ticket) }}" class="font-mono text-xs font-semibold hover:text-brand">{{ $ticket->number }}</a></td>
                        <td><a href="{{ route('support.show', $ticket) }}" class="font-semibold hover:text-brand">{{ $ticket->subject }}</a></td>
                        <td class="text-mist">{{ $ticket->user->email }}</td>
                        <td class="text-mist">{{ $ticket->vendor?->name ?? 'Plattform-Support' }}</td>
                        <td><x-badge :tone="$ticket->status->tone()">{{ $ticket->status->label() }}</x-badge></td>
                        <td class="text-mist">{{ $ticket->last_reply_at?->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-mist">Keine Tickets vorhanden.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-panel>
    {{ $tickets->links() }}
</x-layouts.panel>
