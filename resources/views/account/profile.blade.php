<x-layouts.panel title="Profil">
    <x-page-header title="Profil & Rechnungsdaten" subtitle="Diese Angaben erscheinen auf deinen Rechnungen." />

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <x-panel title="Persönliche Daten" icon="user">
            <form method="POST" action="{{ route('account.profile.update') }}" class="grid gap-4 sm:grid-cols-2">
                @csrf @method('PUT')
                <x-input label="Anzeigename" name="name" :value="$user->name" required maxlength="60" wrapper-class="sm:col-span-2" />
                <x-input label="Vorname" name="first_name" :value="$user->first_name" autocomplete="given-name" />
                <x-input label="Nachname" name="last_name" :value="$user->last_name" autocomplete="family-name" />
                <x-input label="Straße & Hausnummer" name="street" :value="$user->street" autocomplete="street-address" wrapper-class="sm:col-span-2" />
                <x-input label="PLZ" name="postal_code" :value="$user->postal_code" autocomplete="postal-code" />
                <x-input label="Ort" name="city" :value="$user->city" autocomplete="address-level2" />
                <x-input label="Land (ISO-Code)" name="country" :value="$user->country" maxlength="2" class="uppercase" />
                <div>
                    <span class="label">E-Mail</span>
                    <p class="input cursor-not-allowed opacity-60">{{ $user->email }}</p>
                </div>
                <div class="sm:col-span-2"><button type="submit" class="btn btn-primary">Speichern</button></div>
            </form>
        </x-panel>

        <x-panel title="Passwort ändern" icon="lock" class="h-fit">
            <form method="POST" action="{{ route('account.password.update') }}" class="space-y-4">
                @csrf @method('PUT')
                <x-input label="Aktuelles Passwort" name="current_password" type="password" required autocomplete="current-password" />
                <x-input label="Neues Passwort" name="password" type="password" required autocomplete="new-password" />
                <x-input label="Neues Passwort wiederholen" name="password_confirmation" type="password" required autocomplete="new-password" />
                <button type="submit" class="btn btn-secondary w-full">Passwort ändern</button>
            </form>
        </x-panel>
    </div>
</x-layouts.panel>
