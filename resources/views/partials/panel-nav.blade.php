@php
    $user = auth()->user();
    $groups = [
        'Mein Konto' => [
            ['dashboard', 'Übersicht', 'layout-dashboard', 'dashboard'],
            ['account.licenses.index', 'Lizenzen', 'key-round', 'account.licenses.*'],
            ['account.orders.index', 'Bestellungen', 'receipt', 'account.orders.*'],
            ['account.downloads.index', 'Downloads', 'download', 'account.downloads.*'],
            ['account.favorites.index', 'Favoriten', 'heart', 'account.favorites.*'],
            ['account.tokens.index', 'API-Tokens', 'terminal', 'account.tokens.*'],
            ['support.index', 'Support', 'life-buoy', 'support.*'],
            ['account.profile.edit', 'Profil', 'user', 'account.profile.*'],
        ],
    ];

    if ($user->vendor) {
        $groups['Vendor-Portal'] = [
            ['vendor.dashboard', 'Dashboard', 'chart-column', 'vendor.dashboard'],
            ['vendor.plugins.index', 'Plugins', 'package', 'vendor.plugins.*'],
            ['vendor.payouts', 'Auszahlungen', 'wallet', 'vendor.payouts'],
            ['vendor.settlements.index', 'Abrechnungen', 'file-text', 'vendor.settlements.*'],
            ['vendor.tickets.index', 'Studio-Tickets', 'message-square', 'vendor.tickets.*'],
            ['vendor.profile.edit', 'Studio-Profil', 'store', 'vendor.profile.*'],
        ];
    } else {
        $groups['Mein Konto'][] = ['vendor.apply', 'Vendor werden', 'rocket', 'vendor.apply'];
    }

    if ($user->isAdmin()) {
        $groups['Administration'] = [
            ['admin.dashboard', 'Kontrollzentrum', 'gauge', 'admin.dashboard'],
            ['admin.moderation.index', 'Moderation', 'shield-check', 'admin.moderation.*'],
            ['admin.plugins.index', 'Plugins', 'boxes', 'admin.plugins.*'],
            ['admin.vendors.index', 'Vendoren', 'store', 'admin.vendors.*'],
            ['admin.users.index', 'Benutzer', 'users', 'admin.users.*'],
            ['admin.orders.index', 'Bestellungen', 'shopping-bag', 'admin.orders.*'],
            ['admin.settlements.index', 'Abrechnungen', 'banknote', 'admin.settlements.*'],
            ['admin.sales.index', 'Sale-Aktionen', 'percent', 'admin.sales.*'],
            ['admin.announcements.index', 'Ankündigungen', 'megaphone', 'admin.announcements.*'],
            ['admin.categories.index', 'Kategorien', 'layout-grid', 'admin.categories.*'],
            ['admin.activations.index', 'Monitoring', 'activity', 'admin.activations.*'],
            ['admin.tickets.index', 'Tickets', 'life-buoy', 'admin.tickets.*'],
            ['admin.settings.edit', 'Einstellungen', 'settings', 'admin.settings.*'],
        ];
    }
@endphp
<nav class="space-y-6 lg:sticky lg:top-24" aria-label="Bereichsnavigation">
    @foreach ($groups as $heading => $items)
        <div>
            <p class="mb-2 px-3 font-display text-[0.7rem] font-semibold tracking-[0.18em] text-mist/70 uppercase">{{ $heading }}</p>
            <ul class="space-y-0.5">
                @foreach ($items as [$route, $label, $icon, $pattern])
                    <li>
                        <a href="{{ route($route) }}" @class([
                            'flex items-center gap-3 rounded px-3 py-2 text-sm transition',
                            'bg-brand/10 font-semibold text-brand' => request()->routeIs($pattern),
                            'text-mist hover:bg-ink-800 hover:text-snow' => ! request()->routeIs($pattern),
                        ])>
                            <x-icon :name="$icon" class="size-4" /> {{ $label }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
</nav>
