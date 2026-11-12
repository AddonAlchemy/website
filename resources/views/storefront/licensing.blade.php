@php
    $base = url('/api/v1');
    $productId = $sample?->public_id ?? 'PRD-XXXX-XXXX';
@endphp
<x-layouts.app title="Lizenz-API" description="Dokumentation der Lizenz- und Update-API inklusive Live-Check.">
    <div class="container-page max-w-5xl py-12">
        <h1 class="text-3xl font-bold tracking-tight">LIZENZ-<span class="text-brand">API</span></h1>
        <p class="mt-2 max-w-2xl text-mist">Aktiviere, prüfe und aktualisiere Produkte direkt aus deinem Plugin oder Server heraus. Alle Endpunkte akzeptieren JSON- oder Form-Bodies und antworten mit JSON.</p>

        <div class="mt-8 grid gap-4 sm:grid-cols-3">
            <x-stat label="IP-Limit je Lizenz" :value="$maxIps" icon="network" hint="beliebig viele Instanzen je IP" />
            <x-stat label="Produkte je Subuser-Key" :value="$subkeyMaxPlugins" icon="users" hint="an eine Instanz gebunden" />
            <x-stat label="Sperrfrist nach Widerruf" :value="$subkeyLockDays.' Tage'" icon="clock" hint="bis zur Reaktivierung" />
        </div>

        <x-panel title="Live-Check" icon="shield-check" class="mt-8">
            <form class="grid gap-4 sm:grid-cols-[1fr_1fr_auto]" x-data="{ key: '', product: @js($sample?->public_id ?? ''), result: null, busy: false,
                    async check() {
                        this.busy = true;
                        try {
                            const response = await fetch('{{ route('api.licenses.verify') }}?key=' + encodeURIComponent(this.key) + '&product_id=' + encodeURIComponent(this.product), { headers: { Accept: 'application/json' } });
                            this.result = { status: response.status, body: await response.json() };
                        } finally { this.busy = false; }
                    } }" x-on:submit.prevent="check()">
                <div>
                    <label class="label" for="check-key">Lizenz- oder Subuser-Key</label>
                    <input id="check-key" type="text" class="input font-mono" placeholder="AALC-XXXX-XXXX-XXXX-XXXX" x-model="key" required maxlength="64">
                </div>
                <div>
                    <label class="label" for="check-product">Produkt-ID</label>
                    <input id="check-product" type="text" class="input font-mono" placeholder="PRD-XXXX-XXXX" x-model="product" required maxlength="64">
                </div>
                <div class="flex items-end"><button type="submit" class="btn btn-primary w-full" :disabled="busy">Jetzt prüfen</button></div>
                <template x-if="result">
                    <div class="rounded border px-4 py-3 text-sm sm:col-span-3" :class="result.body.valid ? 'border-brand/40 bg-brand/10' : 'border-red-400/40 bg-red-400/10'">
                        <p class="font-semibold" :class="result.body.valid ? 'text-brand' : 'text-red-300'" x-text="result.body.valid ? 'Gültige Lizenz' : (result.body.genuine ? 'Echter Schlüssel, aber nicht gültig für dieses Produkt' : 'Unbekannter Schlüssel')"></p>
                        <pre class="mt-2 overflow-x-auto font-mono text-xs text-mist" x-text="'HTTP ' + result.status + '\n' + JSON.stringify(result.body, null, 2)"></pre>
                    </div>
                </template>
            </form>
        </x-panel>

        <div class="mt-8 space-y-6">
            @foreach ([
                ['POST', '/activate', 'Aktiviert eine Instanz und registriert die IP-Adresse des Aufrufers. Liefert die Instanz-ID zurück, die für Folgeaufrufe gespeichert werden sollte.', 'key, product_id, [instance_id], [name]', 'valid · product_not_licensed · ip_limit_reached · license_revoked · license_expired · subuser_revoked · subuser_bound_other_instance · unknown_key'],
                ['POST', '/deactivate', 'Entfernt eine Instanz und gibt ihre IP-Adresse wieder frei, sofern sie von keiner weiteren Instanz genutzt wird.', 'key, instance_id', 'ok · unknown_key'],
                ['GET | POST', '/verify', 'Prüft ohne Nebenwirkungen, ob ein Schlüssel echt und für ein Produkt gültig ist.', 'key, product_id', 'ok · product_not_licensed · revoked · not_in_subuser · owner_lost_product · unknown_key'],
                ['GET', '/updates/check', 'Liefert die neueste freigegebene Version inklusive Changelog, Prüfsumme und – bei gültiger Lizenz – einer signierten Download-URL.', 'product_id, [version], [key], [platform]', 'ok · unknown_product · no_release'],
            ] as [$method, $path, $text, $fields, $reasons])
                <x-panel>
                    <div class="flex flex-wrap items-center gap-3">
                        <x-badge tone="brand">{{ $method }}</x-badge>
                        <code class="font-mono text-sm">{{ $base.$path }}</code>
                    </div>
                    <p class="mt-3 text-sm text-mist">{{ $text }}</p>
                    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div><dt class="label">Felder</dt><dd class="font-mono text-xs">{{ $fields }}</dd></div>
                        <div><dt class="label">Antwort-Gründe</dt><dd class="font-mono text-xs text-mist">{{ $reasons }}</dd></div>
                    </dl>
                </x-panel>
            @endforeach
        </div>

        <x-panel title="Beispiele" icon="code-xml" class="mt-8">
            <div x-data="{ lang: 'curl' }">
                <div class="mb-4 flex gap-1 border-b border-line">
                    @foreach (['curl' => 'cURL', 'php' => 'PHP', 'node' => 'Node.js', 'python' => 'Python'] as $key => $label)
                        <button type="button" class="border-b-2 px-3 py-2 font-display text-sm font-semibold" :class="lang === '{{ $key }}' ? 'border-brand text-brand' : 'border-transparent text-mist'" x-on:click="lang = '{{ $key }}'">{{ $label }}</button>
                    @endforeach
                </div>
<pre x-show="lang === 'curl'" class="overflow-x-auto rounded border border-line bg-ink-950 p-4 font-mono text-xs leading-relaxed text-mist">curl -X POST {{ $base }}/activate \
  -H "Content-Type: application/json" \
  -d '{"key":"AALC-XXXX-XXXX-XXXX-XXXX","product_id":"{{ $productId }}","name":"Lobby-01"}'

curl "{{ $base }}/updates/check?product_id={{ $productId }}&version=1.0.0&key=AALC-XXXX-XXXX-XXXX-XXXX"</pre>
<pre x-cloak x-show="lang === 'php'" class="overflow-x-auto rounded border border-line bg-ink-950 p-4 font-mono text-xs leading-relaxed text-mist">$response = file_get_contents('{{ $base }}/activate', false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => json_encode(['key' => $licenseKey, 'product_id' => '{{ $productId }}']),
        'ignore_errors' => true,
    ],
]));

$license = json_decode($response, true);
$isValid = ($license['status'] ?? null) === 'valid';</pre>
<pre x-cloak x-show="lang === 'node'" class="overflow-x-auto rounded border border-line bg-ink-950 p-4 font-mono text-xs leading-relaxed text-mist">const response = await fetch('{{ $base }}/activate', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ key: licenseKey, product_id: '{{ $productId }}', instance_id: storedInstanceId }),
});

const license = await response.json();
if (license.status !== 'valid') throw new Error(license.reason);</pre>
<pre x-cloak x-show="lang === 'python'" class="overflow-x-auto rounded border border-line bg-ink-950 p-4 font-mono text-xs leading-relaxed text-mist">import requests

response = requests.post('{{ $base }}/activate', json={
    'key': license_key,
    'product_id': '{{ $productId }}',
})

license = response.json()
is_valid = license.get('status') == 'valid'</pre>
            </div>
        </x-panel>

        <x-panel title="Authentifizierte Endpunkte" icon="lock" class="mt-8">
            <p class="text-sm text-mist">Für kontobezogene Abfragen erstellst du in deinem Konto einen <a href="{{ route('account.tokens.index') }}" class="text-brand hover:underline">API-Token</a> und sendest ihn als <code class="kbd">Authorization: Bearer &lt;token&gt;</code>.</p>
            <ul class="mt-4 space-y-2 font-mono text-xs">
                <li><x-badge tone="sky">GET</x-badge> {{ $base }}/me</li>
                <li><x-badge tone="sky">GET</x-badge> {{ $base }}/licenses</li>
                <li><x-badge tone="sky">GET</x-badge> {{ $base }}/licenses/{key}/download</li>
                <li><x-badge>GET</x-badge> {{ $base }}/plugins <span class="font-sans text-mist">(öffentlich)</span></li>
                <li><x-badge>GET</x-badge> {{ $base }}/plugins/{product_id}/versions <span class="font-sans text-mist">(öffentlich)</span></li>
            </ul>
        </x-panel>
    </div>
</x-layouts.app>
