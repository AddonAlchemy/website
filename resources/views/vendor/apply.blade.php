<x-layouts.panel title="Vendor werden">
    <x-page-header title="Vendor werden" subtitle="Verkaufe deine Plugins über den Marktplatz – wir übernehmen Lizenzierung, Auslieferung und Abrechnung." />

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <x-panel title="Bewerbung" icon="rocket">
            <form method="POST" action="{{ route('vendor.apply.store') }}" class="space-y-4">
                @csrf
                <x-input label="Studio-Name" name="name" required maxlength="80" hint="Unter diesem Namen erscheinen deine Plugins im Katalog." />
                <x-input label="Kurzbeschreibung (optional)" name="tagline" maxlength="160" />
                <x-input label="Website (optional)" name="website" type="url" placeholder="https://" />
                <label class="flex items-start gap-2.5 text-xs leading-relaxed text-mist">
                    <input type="checkbox" name="terms" value="1" class="checkbox mt-0.5" required>
                    <span>Ich akzeptiere die <a href="{{ route('legal.show', 'verkaeufer-bedingungen') }}" target="_blank" class="text-brand hover:underline">Verkäufer-Bedingungen</a>.</span>
                </label>
                <button type="submit" class="btn btn-primary">Bewerbung absenden</button>
            </form>
        </x-panel>

        <x-panel title="So funktioniert es" icon="list-checks" class="h-fit">
            <ol class="space-y-4 text-sm text-mist">
                <li class="flex gap-3"><span class="font-display font-bold text-brand">01</span> Bewerbung absenden – unser Team prüft dein Studio.</li>
                <li class="flex gap-3"><span class="font-display font-bold text-brand">02</span> Plugin anlegen, Version als ZIP hochladen und zur Prüfung einreichen.</li>
                <li class="flex gap-3"><span class="font-display font-bold text-brand">03</span> Nach der Freigabe erscheint dein Plugin im Katalog. Verkäufe findest du in deinen Abrechnungen.</li>
            </ol>
        </x-panel>
    </div>
</x-layouts.panel>
