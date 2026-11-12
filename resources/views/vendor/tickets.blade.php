<x-layouts.panel title="Studio-Tickets">
    <x-page-header title="Studio-Tickets" subtitle="Support-Anfragen von Kunden an dein Studio." />

    @if ($tickets->isEmpty())
        <x-empty icon="message-square" title="Keine Tickets" text="Aktuell liegen keine Anfragen an dein Studio vor." />
    @else
        <x-panel flush>
            <table class="table-grid">
                <thead><tr><th>Ticket</th><th>Betreff</th><th>Kunde</th><th>Produkt</th><th>Status</th><th>Aktualisiert</th></tr></thead>
                <tbody>
                    @foreach ($tickets as $ticket)
                        <tr>
                            <td><a href="{{ route('support.show', $ticket) }}" class="font-mono text-xs font-semibold hover:text-brand">{{ $ticket->number }}</a></td>
                            <td><a href="{{ route('support.show', $ticket) }}" class="font-semibold hover:text-brand">{{ $ticket->subject }}</a></td>
                            <td class="text-mist">{{ $ticket->user->name }}</td>
                            <td class="text-mist">{{ $ticket->plugin?->name ?? '–' }}</td>
                            <td><x-badge :tone="$ticket->status->tone()">{{ $ticket->status->label() }}</x-badge></td>
                            <td class="text-mist">{{ $ticket->last_reply_at?->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-panel>
        {{ $tickets->links() }}
    @endif
</x-layouts.panel>
