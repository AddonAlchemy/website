<x-layouts.panel title="Plugin anlegen">
    <x-page-header title="Neues Plugin" subtitle="Lege zuerst das Listing an – Versionen und Screenshots folgen im nächsten Schritt.">
        <a href="{{ route('vendor.plugins.index') }}" class="btn btn-secondary btn-sm"><x-icon name="arrow-left" class="size-4" /> Zurück</a>
    </x-page-header>

    <x-panel title="Listing" icon="package">
        <form method="POST" action="{{ route('vendor.plugins.store') }}" enctype="multipart/form-data">
            @csrf
            @include('vendor.plugins._form')
            <div class="mt-6"><button type="submit" class="btn btn-primary">Plugin anlegen</button></div>
        </form>
    </x-panel>
</x-layouts.panel>
