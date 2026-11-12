@php($user = auth()->user())
<header class="sticky top-0 z-40 border-b border-line bg-ink-900/85 backdrop-blur-md" x-data="{ menu: false, panel: null }" x-on:keydown.escape.window="panel = null; menu = false">
    <div class="container-page flex h-16 items-center gap-2 sm:gap-4">
        <a href="{{ route('home') }}" class="shrink-0 font-display text-lg font-extrabold tracking-wider sm:text-xl">
            {{ $settings->get('brand_first') }}<span class="text-brand">{{ $settings->get('brand_second') }}</span>
        </a>

        <nav class="ml-4 hidden items-center gap-6 text-sm font-medium text-mist lg:flex">
            <a href="{{ route('plugins.index') }}" @class(['transition hover:text-snow', 'text-snow' => request()->routeIs('plugins.*')])>Plugins</a>
            <a href="{{ route('vendors.index') }}" @class(['transition hover:text-snow', 'text-snow' => request()->routeIs('vendors.*')])>Studios</a>
            <a href="{{ route('licensing.docs') }}" @class(['transition hover:text-snow', 'text-snow' => request()->routeIs('licensing.docs')])>Lizenz-API</a>
            <a href="{{ route('support.index') }}" @class(['transition hover:text-snow', 'text-snow' => request()->routeIs('support.*')])>Support</a>
        </nav>

        <div class="relative ml-auto hidden max-w-xs flex-1 md:block" x-data="liveSearch('{{ route('search.suggest') }}')" x-on:click.outside="close()">
            <form action="{{ route('plugins.index') }}" method="GET" role="search" x-on:submit="go($event)">
                <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-mist" />
                <input type="search" name="q" autocomplete="off" placeholder="Plugins suchen …" aria-label="Plugins suchen"
                       class="input !py-2 pl-9" x-model="term" x-on:input="onInput()" x-on:focus="open = results.length > 0"
                       x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)">
            </form>
            <div x-cloak x-show="open" x-transition.opacity class="card absolute top-full right-0 left-0 mt-2 overflow-hidden shadow-2xl shadow-black/60 md:w-96">
                <template x-for="(result, index) in results" :key="result.url">
                    <a :href="result.url" class="flex items-center gap-3 border-b border-line/60 px-4 py-3 transition hover:bg-ink-700" :class="index === active && 'bg-ink-700'">
                        <template x-if="result.icon"><img :src="result.icon" alt="" class="size-9 rounded object-cover"></template>
                        <template x-if="!result.icon"><span class="flex size-9 items-center justify-center rounded bg-ink-950 text-brand"><x-icon name="package" class="size-4" /></span></template>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold" x-text="result.name"></span>
                            <span class="block truncate text-xs text-mist" x-text="result.vendor + ' · ' + result.category"></span>
                        </span>
                        <span class="font-display text-sm font-bold text-brand" x-text="result.price"></span>
                    </a>
                </template>
                <p x-show="!loading && results.length === 0" class="px-4 py-5 text-center text-sm text-mist">Keine Treffer gefunden.</p>
                <a x-show="more && results.length > 0" :href="more" class="block px-4 py-2.5 text-center font-display text-xs font-semibold tracking-wider text-brand uppercase hover:bg-ink-700">Alle Ergebnisse anzeigen</a>
            </div>
        </div>

        <div class="ml-auto flex items-center gap-0.5 sm:gap-1 md:ml-0">
            @auth
                <a href="{{ route('account.favorites.index') }}" class="hidden rounded p-2 text-mist transition hover:text-brand sm:block" title="Favoriten">
                    <x-icon name="heart" />
                </a>

                <div class="relative">
                    <button type="button" class="relative rounded p-2 text-mist transition hover:text-brand" title="Ankündigungen" x-on:click="panel = panel === 'news' ? null : 'news'">
                        <x-icon name="megaphone" />
                        @if ($unreadAnnouncements > 0)
                            <span class="absolute top-0.5 right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[0.6rem] font-bold text-white">{{ $unreadAnnouncements > 9 ? '9+' : $unreadAnnouncements }}</span>
                        @endif
                    </button>
                    <div x-cloak x-show="panel === 'news'" x-transition.opacity x-on:click.outside="panel = null" class="card absolute right-0 mt-2 flex max-h-[26rem] w-80 max-w-[86vw] flex-col overflow-hidden shadow-2xl shadow-black/60">
                        <h4 class="border-b border-line px-4 py-3 text-sm font-semibold">Ankündigungen</h4>
                        <div class="overflow-y-auto">
                            @forelse ($announcements as $announcement)
                                <div class="flex gap-3 border-b border-line/60 px-4 py-3">
                                    <x-icon :name="$announcement->type->icon()" class="mt-0.5 size-4" style="color: {{ $announcement->type->color() }}" />
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold">{{ $announcement->title }}</p>
                                        <p class="mt-0.5 text-xs leading-relaxed text-mist">{{ $announcement->body }}</p>
                                        <p class="mt-1 text-[0.7rem] text-mist/60">{{ $announcement->created_at->format('d.m.Y H:i') }}</p>
                                    </div>
                                </div>
                            @empty
                                <p class="px-4 py-6 text-center text-sm text-mist">Keine Ankündigungen.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="relative">
                    <button type="button" class="relative rounded p-2 text-mist transition hover:text-brand" title="Benachrichtigungen" x-on:click="panel = panel === 'inbox' ? null : 'inbox'">
                        <x-icon name="bell" />
                        @if ($unreadNotifications > 0)
                            <span class="absolute top-0.5 right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[0.6rem] font-bold text-white">{{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}</span>
                        @endif
                    </button>
                    <div x-cloak x-show="panel === 'inbox'" x-transition.opacity x-on:click.outside="panel = null" class="card absolute right-0 mt-2 flex max-h-[26rem] w-80 max-w-[86vw] flex-col overflow-hidden shadow-2xl shadow-black/60">
                        <h4 class="border-b border-line px-4 py-3 text-sm font-semibold">Benachrichtigungen</h4>
                        <div class="overflow-y-auto">
                            @forelse ($notifications as $notification)
                                <a href="{{ $notification->data['url'] ?? '#' }}" @class(['flex gap-3 border-b border-line/60 px-4 py-3 transition hover:bg-ink-700', 'bg-brand/5' => $notification->read_at === null])>
                                    <x-icon :name="$notification->data['icon'] ?? 'bell'" class="mt-0.5 size-4 text-brand" />
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold">{{ $notification->data['title'] }}</p>
                                        <p class="mt-0.5 text-xs leading-relaxed text-mist">{{ $notification->data['body'] }}</p>
                                        <p class="mt-1 text-[0.7rem] text-mist/60">{{ $notification->created_at->format('d.m.Y H:i') }}</p>
                                    </div>
                                </a>
                            @empty
                                <p class="px-4 py-6 text-center text-sm text-mist">Keine Benachrichtigungen.</p>
                            @endforelse
                        </div>
                        @if ($unreadNotifications + $unreadAnnouncements > 0)
                            <form method="POST" action="{{ route('account.notifications.read') }}" class="border-t border-line">
                                @csrf
                                <button type="submit" class="w-full px-4 py-2.5 font-display text-xs font-semibold tracking-wider text-brand uppercase hover:bg-brand/10">Alle als gelesen markieren</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endauth

            <a href="{{ route('cart.show') }}" class="flex items-center gap-1.5 rounded p-2 text-mist transition hover:text-brand" title="Warenkorb">
                <x-icon name="shopping-cart" />
                <span class="rounded-full bg-brand px-1.5 text-xs font-bold text-black">{{ $cartCount }}</span>
            </a>

            @auth
                <div class="relative hidden sm:block">
                    <button type="button" class="btn btn-muted btn-sm ml-2" x-on:click="panel = panel === 'account' ? null : 'account'">
                        <x-icon name="circle-user" class="size-4" /> {{ \Illuminate\Support\Str::limit($user->name, 14) }}
                    </button>
                    <div x-cloak x-show="panel === 'account'" x-transition.opacity x-on:click.outside="panel = null" class="card absolute right-0 mt-2 w-56 overflow-hidden py-1 text-sm shadow-2xl shadow-black/60">
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-4 py-2 hover:bg-ink-700"><x-icon name="layout-dashboard" class="size-4 text-mist" /> Mein Konto</a>
                        <a href="{{ route('account.licenses.index') }}" class="flex items-center gap-2 px-4 py-2 hover:bg-ink-700"><x-icon name="key-round" class="size-4 text-mist" /> Lizenzen</a>
                        @if ($user->vendor)
                            <a href="{{ route('vendor.dashboard') }}" class="flex items-center gap-2 px-4 py-2 hover:bg-ink-700"><x-icon name="store" class="size-4 text-mist" /> Vendor-Portal</a>
                        @endif
                        @if ($user->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 px-4 py-2 hover:bg-ink-700"><x-icon name="gauge" class="size-4 text-mist" /> Administration</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}" class="mt-1 border-t border-line pt-1">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2 px-4 py-2 text-red-400 hover:bg-ink-700"><x-icon name="log-out" class="size-4" /> Abmelden</button>
                        </form>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}" class="btn btn-muted btn-sm ml-2 hidden sm:inline-flex">Login</a>
            @endauth

            <button type="button" class="rounded p-2 text-mist hover:text-snow lg:hidden" x-on:click="menu = !menu" aria-label="Menü">
                <x-icon name="menu" x-show="!menu" /><x-icon name="x" x-cloak x-show="menu" />
            </button>
        </div>
    </div>

    <div x-cloak x-show="menu" x-collapse class="border-t border-line bg-ink-950 lg:hidden">
        <div class="container-page space-y-1 py-4 text-sm">
            <form action="{{ route('plugins.index') }}" method="GET" class="relative mb-3 md:hidden">
                <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-mist" />
                <input type="search" name="q" placeholder="Plugins suchen …" class="input pl-9">
            </form>
            <a href="{{ route('plugins.index') }}" class="block rounded px-3 py-2 hover:bg-ink-800">Plugins</a>
            <a href="{{ route('vendors.index') }}" class="block rounded px-3 py-2 hover:bg-ink-800">Studios</a>
            <a href="{{ route('licensing.docs') }}" class="block rounded px-3 py-2 hover:bg-ink-800">Lizenz-API</a>
            <a href="{{ route('support.index') }}" class="block rounded px-3 py-2 hover:bg-ink-800">Support</a>
            @auth
                <a href="{{ route('dashboard') }}" class="block rounded px-3 py-2 hover:bg-ink-800">Mein Konto</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="block w-full rounded px-3 py-2 text-left text-red-400 hover:bg-ink-800">Abmelden</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="block rounded px-3 py-2 hover:bg-ink-800">Login</a>
                <a href="{{ route('register') }}" class="block rounded px-3 py-2 text-brand hover:bg-ink-800">Konto erstellen</a>
            @endauth
        </div>
    </div>
</header>
