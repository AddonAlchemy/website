@php($plugin ??= null)
<div class="grid gap-4 sm:grid-cols-2">
    <x-input label="Name" name="name" :value="$plugin?->name" required maxlength="80" wrapper-class="sm:col-span-2" />
    <x-input label="Kurzbeschreibung" name="summary" :value="$plugin?->summary" required maxlength="255" hint="Erscheint auf Karten und in Suchergebnissen." wrapper-class="sm:col-span-2" />

    <x-field label="Kategorie" name="category_id">
        <select id="category_id" name="category_id" class="input" required>
            <option value="" disabled @selected(! old('category_id', $plugin?->category_id))>Bitte wählen</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((int) old('category_id', $plugin?->category_id) === $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
    </x-field>

    <x-field label="Lizenzmodell" name="license_model">
        <select id="license_model" name="license_model" class="input" required>
            @foreach ($licenseModels as $model)
                <option value="{{ $model->value }}" @selected(old('license_model', $plugin?->license_model->value) === $model->value)>{{ $model->label() }}</option>
            @endforeach
        </select>
    </x-field>

    <x-input :label="'Preis ('.setting('currency').')'" name="price" inputmode="decimal" required
             :value="number_format(($plugin?->price_cents ?? 0) / 100, 2, ',', '')"
             :hint="'0 = gratis, sonst mindestens '.money($minPriceCents).'.'" />

    <x-input label="Tags (kommagetrennt)" name="tags" :value="$plugin?->tags->pluck('name')->join(', ')" maxlength="200" hint="Maximal 8 Tags." />

    <div class="sm:col-span-2">
        <div class="mb-1.5 flex items-center justify-between">
            <label for="description" class="label !mb-0">Beschreibung (Markdown)</label>
            <span class="text-xs text-mist">Überschriften, Listen, Code-Blöcke, Tabellen und Links werden unterstützt.</span>
        </div>
        <textarea id="description" name="description" rows="14" class="input font-mono" maxlength="30000">{{ old('description', $plugin?->description) }}</textarea>
        @error('description') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        <label for="icon" class="label">Icon (PNG, JPG oder WebP, max. {{ (int) (config('marketplace.media.max_image_kb') / 1024) }} MB)</label>
        <div class="flex items-center gap-4">
            @if ($plugin)
                <x-plugin-icon :plugin="$plugin" />
            @endif
            <input type="file" id="icon" name="icon" accept="image/png,image/jpeg,image/webp" class="input file:mr-4 file:rounded file:border-0 file:bg-line file:px-3 file:py-1.5 file:text-sm file:text-snow">
        </div>
        @error('icon') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
    </div>
</div>
