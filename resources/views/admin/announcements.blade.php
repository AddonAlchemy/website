<x-layouts.panel title="Ankündigungen">
    <x-page-header title="Ankündigungen" subtitle="Informiere Besucher, Kunden oder Vendoren. Sale-, Aktions- und Event-Meldungen erscheinen zusätzlich als Banner." />

    <div class="grid gap-6 xl:grid-cols-[24rem_minmax(0,1fr)]">
        <x-panel title="Neue Ankündigung" icon="megaphone" class="h-fit">
            <form method="POST" action="{{ route('admin.announcements.store') }}" class="space-y-4">
                @csrf
                <x-input label="Titel" name="title" required maxlength="100" />
                <x-textarea label="Text" name="body" rows="4" required maxlength="500" />
                <div class="grid grid-cols-2 gap-3">
                    <x-field label="Typ" name="type">
                        <select id="type" name="type" class="input">
                            @foreach ($types as $type)
                                <option value="{{ $type->value }}" @selected(old('type') === $type->value)>{{ $type->label() }}{{ $type->isBanner() ? ' (Banner)' : '' }}</option>
                            @endforeach
                        </select>
                    </x-field>
                    <x-field label="Zielgruppe" name="audience">
                        <select id="audience" name="audience" class="input">
                            @foreach ($audiences as $audience)
                                <option value="{{ $audience->value }}" @selected(old('audience') === $audience->value)>{{ $audience->label() }}</option>
                            @endforeach
                        </select>
                    </x-field>
                </div>
                <button type="submit" class="btn btn-primary w-full">Senden</button>
            </form>
        </x-panel>

        <x-panel title="Gesendet" icon="history" flush>
            @forelse ($items as $item)
                <div class="flex items-start gap-4 border-b border-line/60 px-5 py-4 last:border-b-0">
                    <x-icon :name="$item->type->icon()" class="mt-0.5" style="color: {{ $item->type->color() }}" />
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold">{{ $item->title }}</p>
                        <p class="mt-0.5 text-sm text-mist">{{ $item->body }}</p>
                        <p class="mt-1.5 text-xs text-mist/70">{{ $item->type->label() }} · {{ $item->audience->label() }} · {{ $item->created_at->format('d.m.Y H:i') }} · {{ $item->readers_count }} gelesen</p>
                    </div>
                    <form method="POST" action="{{ route('admin.announcements.destroy', $item) }}" data-confirm="Ankündigung löschen?">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-mist hover:text-red-400" title="Löschen"><x-icon name="trash-2" class="size-4" /></button>
                    </form>
                </div>
            @empty
                <p class="p-5 text-sm text-mist">Noch keine Ankündigungen gesendet.</p>
            @endforelse
        </x-panel>
    </div>
    {{ $items->links() }}
</x-layouts.panel>
