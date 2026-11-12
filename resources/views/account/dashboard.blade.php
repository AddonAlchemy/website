<x-layouts.panel title="Mein Konto">
    <x-page-header :title="'Hallo, '.$user->name" subtitle="Deine Lizenzen, Bestellungen und Downloads auf einen Blick.">
        <a href="{{ route('plugins.index') }}" class="btn btn-secondary btn-sm"><x-icon name="search" class="size-4" /> Katalog durchsuchen</a>
    </x-page-header>

    @if ($vendor && ! $vendor->isApproved())
        <div class="mb-6 flex items-center gap-3 rounded border border-amber-400/30 bg-amber-400/10 px-4 py-3 text-sm text-amber-200">
            <x-icon name="clock" class="size-4" /> Dein Studio „{{ $vendor->name }}“ hat den Status: {{ $vendor->status->label() }}.
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Lizenzen" :value="$user->licenses_count" icon="key-round" :href="route('account.licenses.index')" />
        <x-stat label="Bestellungen" :value="$user->orders_count" icon="receipt" :href="route('account.orders.index')" />
        <x-stat label="Downloads" :value="$user->downloads_count" icon="download" :href="route('account.downloads.index')" />
        <x-stat label="Favoriten" :value="$user->favorites_count" icon="heart" :href="route('account.favorites.index')" />
    </div>

    <div class="mt-8 grid gap-6 xl:grid-cols-2">
        <x-panel title="Aktuelle Lizenzen" icon="key-round" flush>
            <x-slot:actions><a href="{{ route('account.licenses.index') }}" class="text-xs font-semibold text-brand hover:underline">Alle verwalten</a></x-slot:actions>
            @forelse ($licenses as $license)
                <div class="flex items-center gap-4 border-b border-line/60 px-5 py-3.5 last:border-b-0">
                    <x-plugin-icon :plugin="$license->plugin" size="size-10" />
                    <div class="min-w-0 flex-1">
                        <a href="{{ route('plugins.show', $license->plugin) }}" class="block truncate text-sm font-semibold hover:text-brand">{{ $license->plugin->name }}</a>
                        <p class="truncate font-mono text-xs text-mist">{{ $license->key }}</p>
                    </div>
                    @if ($license->isUsable() && $license->plugin->latestRelease)
                        <a href="{{ route('downloads.start', $license->plugin->latestRelease) }}" class="btn btn-secondary btn-sm" title="Aktuelle Version herunterladen"><x-icon name="download" class="size-4" /> v{{ $license->plugin->latestRelease->version }}</a>
                    @else
                        <x-badge :tone="$license->isExpired() ? 'amber' : $license->status->tone()">{{ $license->isExpired() ? 'Abgelaufen' : $license->status->label() }}</x-badge>
                    @endif
                </div>
            @empty
                <div class="p-5"><x-empty icon="key-round" title="Noch keine Lizenzen" text="Nach deinem ersten Kauf findest du hier deine Lizenzschlüssel."><a href="{{ route('plugins.index') }}" class="btn btn-primary btn-sm">Zum Katalog</a></x-empty></div>
            @endforelse
        </x-panel>

        <x-panel title="Letzte Bestellungen" icon="receipt" flush>
            <x-slot:actions><a href="{{ route('account.orders.index') }}" class="text-xs font-semibold text-brand hover:underline">Alle anzeigen</a></x-slot:actions>
            @forelse ($orders as $order)
                <a href="{{ route('account.orders.show', $order) }}" class="flex items-center gap-4 border-b border-line/60 px-5 py-3.5 transition last:border-b-0 hover:bg-ink-700/40">
                    <div class="min-w-0 flex-1">
                        <p class="font-mono text-sm font-semibold">{{ $order->number }}</p>
                        <p class="text-xs text-mist">{{ $order->created_at->format('d.m.Y H:i') }} · {{ $order->items_count }} {{ $order->items_count === 1 ? 'Artikel' : 'Artikel' }}</p>
                    </div>
                    <span class="font-display font-bold">{{ money($order->total_cents) }}</span>
                    <x-badge :tone="$order->status->tone()">{{ $order->status->label() }}</x-badge>
                </a>
            @empty
                <p class="p-5 text-sm text-mist">Du hast noch keine Bestellungen aufgegeben.</p>
            @endforelse
        </x-panel>
    </div>
</x-layouts.panel>
