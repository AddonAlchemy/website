<x-layouts.auth title="Neues Passwort" heading="Neues Passwort vergeben">
    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-input label="E-Mail" name="email" type="email" :value="$email" required />
        <x-input label="Neues Passwort" name="password" type="password" required autocomplete="new-password" />
        <x-input label="Passwort wiederholen" name="password_confirmation" type="password" required autocomplete="new-password" />
        <button type="submit" class="btn btn-primary w-full">Passwort speichern</button>
    </form>
</x-layouts.auth>
