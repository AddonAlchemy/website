<x-layouts.app>
    <section class="relative overflow-hidden bg-[radial-gradient(circle_at_top,color-mix(in_oklab,var(--color-brand)_16%,transparent)_0%,transparent_60%)]">
        <div class="container-page flex flex-col items-center py-20 text-center sm:py-28">
            <h1 class="animate-rise font-display text-5xl leading-none font-extrabold tracking-[0.08em] sm:text-7xl">
                {{ $settings->get('brand_first') }} <span class="text-brand">{{ $settings->get('brand_second') }}</span>
            </h1>
            <div class="mt-4 flex w-full items-center justify-center gap-5">
                <span class="hidden h-0.5 w-24 bg-line sm:block"></span>
                <p class="font-display text-sm font-bold tracking-[0.3em] text-mist sm:text-base">PLUGINS <span class="mx-1 text-brand">|</span> LIZENZEN <span class="mx-1 text-brand">|</span> SUPPORT</p>
                <span class="hidden h-0.5 w-24 bg-line sm:block"></span>
            </div>
            <p class="mt-8 max-w-xl text-lg text-mist">{{ $settings->get('tagline') }}</p>

            <form action="{{ route('plugins.index') }}" method="GET" class="relative mt-10 w-full max-w-xl">
                <x-icon name="search" class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-mist" />
                <input type="search" name="q" placeholder="Wonach suchst du? z. B. Economy, Anti-Cheat, Tickets …" class="input !rounded-full !py-3.5 !pr-32 !pl-12 !text-base" aria-label="Plugins suchen">
                <button type="submit" class="btn btn-primary absolute top-1/2 right-1.5 -translate-y-1/2 !rounded-full">Suchen</button>
            </form>

            <dl class="mt-12 grid w-full max-w-2xl grid-cols-3 gap-4">
                @foreach ([['Plugins', $stats['plugins']], ['Studios', $stats['vendors']], ['Downloads', $stats['downloads']]] as [$label, $value])
                    <div class="card px-4 py-5">
                        <dd class="font-display text-2xl font-bold text-brand sm:text-3xl">{{ number_format($value, 0, ',', '.') }}</dd>
                        <dt class="mt-1 text-xs tracking-wider text-mist uppercase">{{ $label }}</dt>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    <section class="container-page py-14">
        <div class="mb-8 flex items-end justify-between gap-4">
            <div>
                <h2 class="section-title">Kategorien</h2>
                <p class="mt-1 text-sm text-mist">Finde genau die Erweiterung, die dein Projekt braucht.</p>
            </div>
            <a href="{{ route('plugins.index') }}" class="hidden text-sm font-semibold text-brand hover:underline sm:block">Gesamter Katalog →</a>
        </div>
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            @foreach ($categories as $category)
                <a href="{{ route('plugins.index', ['category' => $category->slug]) }}" class="card card-hover flex items-center gap-4 p-5">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded bg-brand/10 text-brand"><x-icon :name="$category->icon" /></span>
                    <span class="min-w-0">
                        <span class="block truncate font-display font-semibold">{{ $category->name }}</span>
                        <span class="text-xs text-mist">{{ $category->plugins_count }} {{ $category->plugins_count === 1 ? 'Plugin' : 'Plugins' }}</span>
                    </span>
                </a>
            @endforeach
        </div>
    </section>

    @if ($featured->isNotEmpty())
        <section class="border-y border-line bg-ink-950">
            <div class="container-page py-14">
                <div class="mb-8">
                    <h2 class="section-title flex items-center gap-3"><x-icon name="sparkles" class="size-6 text-brand" /> Empfohlen vom Team</h2>
                    <p class="mt-1 text-sm text-mist">Handverlesene Plugins mit herausragender Qualität.</p>
                </div>
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($featured as $plugin)
                        <x-plugin-card :plugin="$plugin" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="container-page py-14">
        <div class="mb-8 text-center">
            <h2 class="section-title">Top 3 Bestseller</h2>
            <p class="mt-1 text-sm text-mist">Die meistgekauften Produkte unserer Community.</p>
        </div>
        @if ($bestsellers->isEmpty())
            <x-empty title="Noch keine Produkte verfügbar" text="Sobald die ersten Plugins freigegeben sind, erscheinen sie hier." />
        @else
            <div class="grid gap-6 md:grid-cols-3">
                @foreach ($bestsellers as $plugin)
                    <x-plugin-card :plugin="$plugin" />
                @endforeach
            </div>
        @endif
    </section>

    @if ($newest->isNotEmpty())
        <section class="container-page pb-14">
            <div class="mb-8 flex items-end justify-between gap-4">
                <div>
                    <h2 class="section-title">Frisch veröffentlicht</h2>
                    <p class="mt-1 text-sm text-mist">Die neuesten Zugänge im Marktplatz.</p>
                </div>
                <a href="{{ route('plugins.index', ['sort' => 'newest']) }}" class="btn btn-secondary btn-sm">Alle ansehen</a>
            </div>
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($newest as $plugin)
                    <x-plugin-card :plugin="$plugin" />
                @endforeach
            </div>
        </section>
    @endif

    <section id="licensing" class="border-y border-line bg-ink-950">
        <div class="container-page py-16">
            <h2 class="section-title text-center">Unser Lizenzierungs-System</h2>
            <div class="mt-10 grid gap-6 md:grid-cols-3">
                @foreach ([
                    ['01', 'Produkt wählen', 'Kaufe ein Plugin oder eine Software-Lizenz deiner Wahl direkt über unseren sicheren Checkout.'],
                    ['02', 'Key erhalten', 'Nach dem Kauf wird sofort ein kryptografisch sicherer Lizenzschlüssel in deinem Konto generiert.'],
                    ['03', 'Live-Validierung', 'Binde unsere schlanke API in dein Produkt ein. Aktivierung und Update-Checks erfolgen in Echtzeit.'],
                ] as [$number, $heading, $text])
                    <div class="card p-7">
                        <p class="font-display text-4xl font-extrabold text-brand/80">{{ $number }}</p>
                        <h3 class="mt-3 text-lg font-semibold">{{ $heading }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-mist">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="container-page py-16">
        <h2 class="section-title text-center">Warum {{ \Illuminate\Support\Str::title($settings->get('brand_first').' '.$settings->get('brand_second')) }}?</h2>
        <div class="mt-10 grid gap-6 md:grid-cols-3">
            @foreach ([
                ['shield-check', 'Geprüfte Qualität', 'Jedes Plugin und jede Version durchläuft eine Moderation, bevor sie im Katalog erscheint.'],
                ['store', 'Einfacher Verkauf', 'Lade als Vendor eigene Plugins hoch, bestimme den Preis – Lizenzierung, Abrechnung und Auslieferung übernehmen wir.'],
                ['refresh-cw', 'Automatisierte Updates', 'Kunden erhalten neue Versionen über signierte Download-Links und den Update-Endpoint der API.'],
            ] as [$icon, $heading, $text])
                <div class="border-l-2 border-brand pl-6">
                    <x-icon :name="$icon" class="size-7 text-brand" />
                    <h3 class="mt-3 text-lg font-semibold">{{ $heading }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-mist">{{ $text }}</p>
                </div>
            @endforeach
        </div>
        <div class="card mt-14 flex flex-wrap items-center justify-between gap-6 bg-[linear-gradient(120deg,color-mix(in_oklab,var(--color-brand)_12%,transparent),transparent_60%)] p-8">
            <div>
                <h3 class="text-xl font-bold">Du entwickelst selbst Plugins?</h3>
                <p class="mt-1 text-sm text-mist">Werde Vendor und erreiche tausende Server-Betreiber und Communities.</p>
            </div>
            <a href="{{ route('vendor.apply') }}" class="btn btn-primary">Jetzt Vendor werden <x-icon name="arrow-right" class="size-4" /></a>
        </div>
    </section>
</x-layouts.app>
