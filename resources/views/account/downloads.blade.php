<x-layouts.panel title="Downloads">
    <x-page-header title="Download-Historie" subtitle="Alle Dateien, die du über dein Konto heruntergeladen hast." />

    @if ($downloads->isEmpty())
        <x-empty icon="download" title="Noch keine Downloads" text="Sobald du eine Version herunterlädst, erscheint sie in dieser Liste." />
    @else
        <x-panel flush>
            <table class="table-grid">
                <thead><tr><th>Plugin</th><th>Version</th><th>Datei</th><th>Datum</th><th></th></tr></thead>
                <tbody>
                    @foreach ($downloads as $download)
                        <tr>
                            <td><a href="{{ route('plugins.show', $download->plugin) }}" class="font-semibold hover:text-brand">{{ $download->plugin->name }}</a></td>
                            <td><x-badge>v{{ $download->version->version }}</x-badge></td>
                            <td class="text-mist">{{ $download->version->file_name }} <span class="text-xs">({{ $download->version->humanFileSize() }})</span></td>
                            <td class="text-mist">{{ $download->created_at->format('d.m.Y H:i') }}</td>
                            <td class="text-right"><a href="{{ route('downloads.start', $download->version) }}" class="text-xs font-semibold text-brand hover:underline">Erneut laden</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-panel>
        {{ $downloads->links() }}
    @endif
</x-layouts.panel>
