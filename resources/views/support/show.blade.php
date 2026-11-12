<x-layouts.panel :title="'Ticket '.$ticket->number">
    <x-page-header :title="$ticket->subject" :subtitle="$ticket->number.' · an '.($ticket->vendor?->name ?? 'Plattform-Support').($ticket->plugin ? ' · Produkt: '.$ticket->plugin->name : '')">
        <x-badge :tone="$ticket->status->tone()">{{ $ticket->status->label() }}</x-badge>
        <form method="POST" action="{{ route('support.status', $ticket) }}">
            @csrf @method('PATCH')
            @if ($ticket->isClosed())
                <input type="hidden" name="reopen" value="1">
                <button type="submit" class="btn btn-secondary btn-sm">Wieder öffnen</button>
            @else
                <button type="submit" class="btn btn-secondary btn-sm">Ticket schließen</button>
            @endif
        </form>
    </x-page-header>

    <div class="space-y-4">
        @foreach ($ticket->messages as $message)
            @php($own = $message->user_id === auth()->id())
            <div @class(['flex', 'justify-end' => $own])>
                <article @class(['card max-w-2xl p-4', 'border-brand/40 bg-brand/5' => $own])>
                    <header class="mb-2 flex flex-wrap items-center gap-2 text-xs text-mist">
                        <span class="font-semibold text-snow">{{ $message->user->name }}</span>
                        <x-badge :tone="$message->user->role->tone()">{{ $message->user->role->label() }}</x-badge>
                        <span>{{ $message->created_at->format('d.m.Y H:i') }}</span>
                    </header>
                    <p class="text-sm leading-relaxed whitespace-pre-line">{{ $message->body }}</p>
                </article>
            </div>
        @endforeach
    </div>

    @unless ($ticket->isClosed())
        <form method="POST" action="{{ route('support.reply', $ticket) }}" class="card mt-6 space-y-4 p-5">
            @csrf
            <x-textarea label="Antwort" name="message" rows="4" required maxlength="3000" />
            <button type="submit" class="btn btn-primary btn-sm"><x-icon name="send" class="size-4" /> Antwort senden</button>
        </form>
    @else
        <p class="mt-6 rounded border border-line px-4 py-3 text-sm text-mist">Dieses Ticket ist geschlossen. Öffne es erneut, um zu antworten.</p>
    @endunless
</x-layouts.panel>
