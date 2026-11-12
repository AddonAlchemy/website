<x-layouts.panel title="Support">
    <x-page-header title="Support" subtitle="Erstelle ein Ticket – unser Team oder das zuständige Studio meldet sich bei dir." />

    <div class="grid gap-6 xl:grid-cols-[24rem_minmax(0,1fr)]">
        <x-panel title="Neues Ticket" icon="plus" class="h-fit">
            <form method="POST" action="{{ route('support.store') }}" class="space-y-4">
                @csrf
                @if ($canContactVendors)
                    <x-field label="Empfänger" name="vendor_id">
                        <select id="vendor_id" name="vendor_id" class="input">
                            <option value="">Plattform-Support</option>
                            @foreach ($vendors as $vendor)
                                <option value="{{ $vendor->id }}" @selected((int) old('vendor_id') === $vendor->id)>Studio: {{ $vendor->name }}</option>
                            @endforeach
                        </select>
                    </x-field>
                @endif
                @if ($ownedPlugins->isNotEmpty())
                    <x-field label="Betroffenes Produkt (optional)" name="plugin_id">
                        <select id="plugin_id" name="plugin_id" class="input">
                            <option value="">Kein bestimmtes Produkt</option>
                            @foreach ($ownedPlugins as $plugin)
                                <option value="{{ $plugin->id }}" @selected((int) old('plugin_id') === $plugin->id)>{{ $plugin->name }}</option>
                            @endforeach
                        </select>
                    </x-field>
                @endif
                <x-input label="Betreff" name="subject" required maxlength="120" />
                <x-textarea label="Nachricht" name="message" rows="6" required maxlength="3000" />
                <button type="submit" class="btn btn-primary w-full"><x-icon name="send" class="size-4" /> Ticket erstellen</button>
            </form>
        </x-panel>

        <x-panel title="Meine Tickets" icon="life-buoy" flush>
            @if ($tickets->isEmpty())
                <p class="p-5 text-sm text-mist">Du hast noch keine Tickets erstellt.</p>
            @else
                <table class="table-grid">
                    <thead><tr><th>Ticket</th><th>Betreff</th><th>Empfänger</th><th>Status</th><th>Aktualisiert</th></tr></thead>
                    <tbody>
                        @foreach ($tickets as $ticket)
                            <tr>
                                <td><a href="{{ route('support.show', $ticket) }}" class="font-mono text-xs font-semibold hover:text-brand">{{ $ticket->number }}</a></td>
                                <td><a href="{{ route('support.show', $ticket) }}" class="font-semibold hover:text-brand">{{ $ticket->subject }}</a></td>
                                <td class="text-mist">{{ $ticket->vendor?->name ?? 'Plattform-Support' }}</td>
                                <td><x-badge :tone="$ticket->status->tone()">{{ $ticket->status->label() }}</x-badge></td>
                                <td class="text-mist">{{ $ticket->last_reply_at?->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-panel>
    </div>
    {{ $tickets->links() }}
</x-layouts.panel>
