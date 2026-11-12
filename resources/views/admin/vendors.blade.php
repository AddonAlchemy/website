<x-layouts.panel title="Vendoren">
    <x-page-header title="Vendoren" subtitle="Bewerbungen prüfen und Studios verwalten." />

    <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold">Offene Bewerbungen <x-badge tone="amber">{{ $applications->count() }}</x-badge></h2>
    @forelse ($applications as $vendor)
        <article class="card mb-4 flex flex-wrap items-center gap-4 p-5" x-data="{ rejecting: false }">
            <div class="min-w-0 flex-1">
                <h3 class="font-semibold">{{ $vendor->name }}</h3>
                <p class="text-xs text-mist">{{ $vendor->user->email }} · beworben am {{ $vendor->created_at->format('d.m.Y') }}@if ($vendor->website) · <a href="{{ $vendor->website }}" target="_blank" rel="noopener nofollow" class="text-brand hover:underline">Website</a>@endif</p>
                @if ($vendor->tagline) <p class="mt-1 text-sm text-mist">{{ $vendor->tagline }}</p> @endif
            </div>
            <form method="POST" action="{{ route('admin.vendors.update', $vendor) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="approved">
                <button type="submit" class="btn btn-primary btn-sm"><x-icon name="check" class="size-4" /> Freischalten</button>
            </form>
            <button type="button" class="btn btn-danger btn-sm" x-on:click="rejecting = !rejecting">Ablehnen</button>
            <form x-cloak x-show="rejecting" method="POST" action="{{ route('admin.vendors.update', $vendor) }}" class="flex w-full gap-2 border-t border-line pt-4">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="rejected">
                <input type="text" name="reason" class="input" placeholder="Begründung (optional)" maxlength="255">
                <button type="submit" class="btn btn-danger btn-sm">Ablehnung senden</button>
            </form>
        </article>
    @empty
        <x-empty icon="circle-check" title="Keine offenen Bewerbungen" class="mb-8" />
    @endforelse

    <x-panel title="Alle Studios" icon="store" flush class="mt-10">
        <table class="table-grid">
            <thead><tr><th>Studio</th><th>Inhaber</th><th>Status</th><th>Plugins</th><th>Seit</th><th></th></tr></thead>
            <tbody>
                @forelse ($vendors as $vendor)
                    <tr>
                        <td class="font-semibold">{{ $vendor->name }}</td>
                        <td class="text-mist">{{ $vendor->user->email }}</td>
                        <td><x-badge :tone="$vendor->status->tone()">{{ $vendor->status->label() }}</x-badge></td>
                        <td>{{ $vendor->plugins_count }}</td>
                        <td class="text-mist">{{ $vendor->approved_at?->format('d.m.Y') ?? '–' }}</td>
                        <td class="text-right">
                            <form method="POST" action="{{ route('admin.vendors.update', $vendor) }}" class="inline">
                                @csrf @method('PATCH')
                                @if ($vendor->isApproved())
                                    <input type="hidden" name="status" value="suspended">
                                    <button type="submit" class="text-xs font-semibold text-red-400 hover:underline">Sperren</button>
                                @else
                                    <input type="hidden" name="status" value="approved">
                                    <button type="submit" class="text-xs font-semibold text-brand hover:underline">Freischalten</button>
                                @endif
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-mist">Noch keine Studios.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-panel>
    {{ $vendors->links() }}
</x-layouts.panel>
