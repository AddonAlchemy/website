<x-layouts.panel title="Studio-Profil">
    <x-page-header title="Studio-Profil" subtitle="Diese Angaben erscheinen auf deiner öffentlichen Studio-Seite.">
        @if ($vendor->isApproved())
            <a href="{{ route('vendors.show', $vendor) }}" class="btn btn-secondary btn-sm"><x-icon name="external-link" class="size-4" /> Öffentliche Seite</a>
        @endif
    </x-page-header>

    <x-panel :title="$vendor->name" icon="store" class="max-w-3xl">
        <form method="POST" action="{{ route('vendor.profile.update') }}" class="space-y-4">
            @csrf @method('PUT')
            <x-input label="Kurzbeschreibung" name="tagline" :value="$vendor->tagline" maxlength="160" />
            <x-textarea label="Über das Studio (Markdown)" name="bio" :value="$vendor->bio" rows="7" maxlength="3000" />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-input label="Website" name="website" type="url" :value="$vendor->website" placeholder="https://" />
                <x-input label="Support-E-Mail" name="support_email" type="email" :value="$vendor->support_email" />
            </div>
            <button type="submit" class="btn btn-primary">Speichern</button>
        </form>
    </x-panel>
</x-layouts.panel>
