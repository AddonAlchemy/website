<x-layouts.auth title="Passwort vergessen" heading="Passwort zurücksetzen" subtitle="Wir senden dir einen Link, mit dem du ein neues Passwort vergeben kannst.">
    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf
        <x-input label="E-Mail" name="email" type="email" required autofocus />
        <button type="submit" class="btn btn-primary w-full">Link anfordern</button>
    </form>
    <x-slot:footer><a href="{{ route('login') }}" class="text-brand hover:underline">Zurück zum Login</a></x-slot:footer>
</x-layouts.auth>
