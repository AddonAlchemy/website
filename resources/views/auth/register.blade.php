<x-layouts.auth title="Registrieren" heading="Konto erstellen">
    <form method="POST" action="{{ route('register.store') }}" class="space-y-4" x-data="{ type: @js(old('account_type', request('type') === 'vendor' ? 'vendor' : 'customer')) }">
        @csrf
        <input type="hidden" name="account_type" :value="type">
        <div class="grid grid-cols-2 gap-1 rounded border border-line bg-ink-950 p-1 text-center font-display text-sm font-semibold">
            <button type="button" class="rounded py-2 transition" :class="type === 'customer' ? 'bg-brand text-black' : 'text-mist hover:text-snow'" x-on:click="type = 'customer'">Kunde</button>
            <button type="button" class="rounded py-2 transition" :class="type === 'vendor' ? 'bg-brand text-black' : 'text-mist hover:text-snow'" x-on:click="type = 'vendor'">Vendor / Studio</button>
        </div>

        <p x-cloak x-show="type === 'vendor'" class="rounded border border-brand/30 bg-brand/5 px-3 py-2.5 text-xs leading-relaxed text-mist">
            Vendor-Konten werden von unserem Team geprüft. Nach der Freischaltung kannst du Plugins einreichen und verkaufen.
        </p>

        <div x-cloak x-show="type === 'vendor'">
            <x-input label="Studio-Name" name="studio" maxlength="80" x-bind:required="type === 'vendor'" />
        </div>
        <x-input label="Anzeigename (optional)" name="name" maxlength="60" autocomplete="nickname" />
        <x-input label="E-Mail" name="email" type="email" required autocomplete="username" />
        <x-input label="Passwort" name="password" type="password" required autocomplete="new-password" hint="Mindestens 8 Zeichen." />
        <x-input label="Passwort wiederholen" name="password_confirmation" type="password" required autocomplete="new-password" />
        <label class="flex items-start gap-2.5 text-xs leading-relaxed text-mist">
            <input type="checkbox" name="terms" value="1" class="checkbox mt-0.5" required>
            <span>Ich akzeptiere die <a href="{{ route('legal.show', 'agb') }}" target="_blank" class="text-brand hover:underline">AGB</a> und die <a href="{{ route('legal.show', 'datenschutz') }}" target="_blank" class="text-brand hover:underline">Datenschutzerklärung</a><span x-show="type === 'vendor'"> sowie die <a href="{{ route('legal.show', 'verkaeufer-bedingungen') }}" target="_blank" class="text-brand hover:underline">Verkäufer-Bedingungen</a></span>.</span>
        </label>
        <button type="submit" class="btn btn-primary w-full" x-text="type === 'vendor' ? 'Als Vendor bewerben' : 'Konto erstellen'">Konto erstellen</button>
    </form>
    <x-slot:footer>Bereits registriert? <a href="{{ route('login') }}" class="text-brand hover:underline">Zum Login</a></x-slot:footer>
</x-layouts.auth>
