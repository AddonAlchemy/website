<x-layouts.panel title="Kategorien">
    <x-page-header title="Kategorien" subtitle="Struktur des Katalogs pflegen." />

    <div class="grid gap-6 xl:grid-cols-[22rem_minmax(0,1fr)]">
        <x-panel title="Neue Kategorie" icon="plus" class="h-fit">
            <form method="POST" action="{{ route('admin.categories.store') }}" class="space-y-4">
                @csrf
                <x-input label="Name" name="name" required maxlength="80" />
                <x-input label="Icon" name="icon" required maxlength="40" value="package" hint="Name eines Icons, z. B. shield, coins, gamepad-2." />
                <x-input label="Beschreibung" name="description" maxlength="255" />
                <x-input label="Position" name="position" type="number" min="0" max="1000" value="0" />
                <button type="submit" class="btn btn-primary w-full">Anlegen</button>
            </form>
        </x-panel>

        <div class="space-y-3">
            @foreach ($categories as $category)
                <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="card grid items-end gap-3 p-4 sm:grid-cols-[2.5rem_minmax(0,1fr)_9rem_5rem_auto]">
                    @csrf @method('PUT')
                    <span class="flex size-10 items-center justify-center rounded bg-brand/10 text-brand"><x-icon :name="$category->icon" /></span>
                    <div>
                        <label class="label" for="name-{{ $category->id }}">Name · {{ $category->plugins_count }} Plugins</label>
                        <input id="name-{{ $category->id }}" type="text" name="name" value="{{ $category->name }}" class="input" required maxlength="80">
                        <input type="hidden" name="description" value="{{ $category->description }}">
                    </div>
                    <div>
                        <label class="label" for="icon-{{ $category->id }}">Icon</label>
                        <input id="icon-{{ $category->id }}" type="text" name="icon" value="{{ $category->icon }}" class="input" required maxlength="40">
                    </div>
                    <div>
                        <label class="label" for="position-{{ $category->id }}">Pos.</label>
                        <input id="position-{{ $category->id }}" type="number" name="position" value="{{ $category->position }}" class="input" min="0" max="1000">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="btn btn-secondary btn-sm" title="Speichern"><x-icon name="save" class="size-4" /></button>
                        <button type="submit" form="delete-category-{{ $category->id }}" class="btn btn-danger btn-sm" title="Löschen" @disabled($category->plugins_count > 0)><x-icon name="trash-2" class="size-4" /></button>
                    </div>
                </form>
                <form id="delete-category-{{ $category->id }}" method="POST" action="{{ route('admin.categories.destroy', $category) }}" data-confirm="Kategorie löschen?">
                    @csrf @method('DELETE')
                </form>
            @endforeach
        </div>
    </div>
</x-layouts.panel>
