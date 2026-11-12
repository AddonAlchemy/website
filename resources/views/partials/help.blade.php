@php
    $helpTopics = [
        ['keywords' => ['aktivier', 'lizenz', 'schlüssel', 'license', 'key'], 'answer' => 'Deine Lizenzschlüssel findest du im Konto unter „Lizenzen“. Trage den Schlüssel in deinem Produkt oder Server ein, um es zu aktivieren.'],
        ['keywords' => ['ip', 'limit'], 'answer' => 'Eine Lizenz darf gleichzeitig auf maximal '.$settings->maxIps().' IP-Adressen laufen – mit beliebig vielen Instanzen je IP. Gib eine IP in der Lizenzverwaltung frei, um eine neue zu nutzen.'],
        ['keywords' => ['subuser', 'freund', 'teilen'], 'answer' => 'Mit einem Subuser-Key gibst du bis zu '.$settings->subkeyMaxPlugins().' Produkte an eine einzelne Instanz frei – IP-unabhängig. Widerruf jederzeit, Reaktivierung erst nach '.$settings->subkeyLockDays().' Tagen.'],
        ['keywords' => ['kauf', 'bestell', 'warenkorb', 'checkout', 'bezahl'], 'answer' => 'Lege Plugins in den Warenkorb und schließe die Bestellung ab. Nach der Freischaltung erscheint die Lizenz in deinem Konto.'],
        ['keywords' => ['gratis', 'kostenlos', 'free'], 'answer' => 'Kostenlose Plugins lädst du direkt auf der Produktseite herunter – die Lizenz wird automatisch deinem Konto hinzugefügt.'],
        ['keywords' => ['rück', 'erstatt', 'refund', 'geld'], 'answer' => 'Bei digitalen Inhalten ist eine Rückerstattung nach der Freischaltung meist ausgeschlossen. Erstelle bei Problemen bitte ein Ticket – wir finden eine Lösung.'],
        ['keywords' => ['vendor', 'verkauf', 'studio', 'anbiet'], 'answer' => 'Du möchtest verkaufen? Bewirb dich als Vendor. Nach der Freischaltung kannst du Plugins einreichen und Versionen veröffentlichen.'],
        ['keywords' => ['update', 'version', 'download'], 'answer' => 'Neue Versionen findest du auf der Produktseite und in deinen Lizenzen. Integrationen fragen Updates über /api/v1/updates/check ab.'],
        ['keywords' => ['api', 'prüf', 'verify', 'echt', 'token'], 'answer' => 'Ob eine Lizenz echt ist, prüfst du read-only über /api/v1/verify. Dokumentation und Live-Check findest du unter „Lizenz-API“.'],
    ];
    $helpGreeting = 'Hallo! Hier bekommst du sofort Antworten auf häufige Fragen zu Lizenzen, Käufen und Aktivierung. Für individuelle Hilfe erstellst du ein Support-Ticket.';
@endphp
<div class="fixed right-4 bottom-4 z-40" x-data="helpCenter(@js($helpTopics), @js($helpGreeting))">
    <div x-cloak x-show="open" x-transition.opacity class="card absolute right-0 bottom-14 flex h-[28rem] max-h-[70vh] w-[21rem] max-w-[calc(100vw-2rem)] flex-col overflow-hidden shadow-2xl shadow-black/70">
        <div class="flex items-center gap-3 border-b border-line bg-ink-950 px-4 py-3">
            <span class="flex size-9 items-center justify-center rounded-full bg-brand text-black"><x-icon name="life-buoy" /></span>
            <div>
                <h4 class="text-sm font-semibold">Hilfe-Center</h4>
                <p class="text-xs text-brand">Sofort-Antworten</p>
            </div>
            <button type="button" class="ml-auto text-mist hover:text-snow" x-on:click="open = false" aria-label="Schließen"><x-icon name="x" /></button>
        </div>
        <div class="flex flex-1 flex-col gap-2.5 overflow-y-auto p-4" x-ref="thread">
            <template x-for="(message, index) in messages" :key="index">
                <div class="flex flex-col gap-2" :class="message.from === 'visitor' ? 'items-end' : 'items-start'">
                    <p class="max-w-[85%] rounded-xl px-3.5 py-2 text-sm leading-relaxed"
                       :class="message.from === 'visitor' ? 'rounded-br-sm bg-brand text-black' : 'rounded-bl-sm bg-ink-950 text-snow'" x-text="message.text"></p>
                    <a x-show="message.escalate" href="{{ route('support.index') }}" class="btn btn-secondary btn-sm !border-brand !text-brand">Support-Ticket erstellen</a>
                </div>
            </template>
        </div>
        <form class="flex gap-2 border-t border-line p-3" x-on:submit.prevent="send()">
            <input type="text" class="input !rounded-full" placeholder="Frage stellen …" x-model="draft" aria-label="Frage">
            <button type="submit" class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand text-black" aria-label="Senden"><x-icon name="send" class="size-4" /></button>
        </form>
    </div>
    <button type="button" class="btn btn-primary !rounded-full shadow-xl shadow-black/50" x-on:click="toggle()">
        <x-icon name="message-circle" /> <span class="hidden sm:inline">Hilfe</span>
    </button>
</div>
