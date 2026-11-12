<x-layouts.auth title="Login" heading="Willkommen zurück" subtitle="Melde dich an, um Lizenzen, Downloads und Bestellungen zu verwalten.">
    <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
        @csrf
        <x-input label="E-Mail" name="email" type="email" required autofocus autocomplete="username" />
        <x-input label="Passwort" name="password" type="password" required autocomplete="current-password" />
        <div class="flex items-center justify-between text-sm">
            <label class="flex items-center gap-2 text-mist"><input type="checkbox" name="remember" value="1" class="checkbox"> Angemeldet bleiben</label>
            <a href="{{ route('password.request') }}" class="text-brand hover:underline">Passwort vergessen?</a>
        </div>
        <button type="submit" class="btn btn-primary w-full">Anmelden</button>
    </form>
    <x-slot:footer>Noch kein Konto? <a href="{{ route('register') }}" class="text-brand hover:underline">Jetzt registrieren</a></x-slot:footer>
</x-layouts.auth>
