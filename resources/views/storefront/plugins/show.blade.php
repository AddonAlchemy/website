<x-layouts.app :title="$plugin->name" :description="$plugin->summary">
    <div class="container-page py-10">
        <nav class="mb-6 flex flex-wrap items-center gap-2 text-xs text-mist" aria-label="Breadcrumb">
            <a href="{{ route('plugins.index') }}" class="hover:text-brand">Plugins</a>
            <x-icon name="chevron-right" class="size-3" />
            <a href="{{ route('plugins.index', ['category' => $plugin->category->slug]) }}" class="hover:text-brand">{{ $plugin->category->name }}</a>
            <x-icon name="chevron-right" class="size-3" />
            <span class="text-snow">{{ $plugin->name }}</span>
        </nav>

        @unless ($plugin->isPublished())
            <div class="mb-6 flex items-center gap-3 rounded border border-amber-400/30 bg-amber-400/10 px-4 py-3 text-sm text-amber-200">
                <x-icon name="eye" class="size-4" /> Vorschau – dieses Plugin ist noch nicht öffentlich sichtbar (Status: {{ $plugin->status->label() }}).
            </div>
        @endunless

        <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="min-w-0">
                <header class="flex items-start gap-5">
                    <x-plugin-icon :plugin="$plugin" size="size-20" />
                    <div class="min-w-0">
                        <h1 class="text-3xl font-bold tracking-tight">{{ $plugin->name }}</h1>
                        <p class="mt-1 text-mist">{{ $plugin->summary }}</p>
                        <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-mist">
                            <a href="{{ route('vendors.show', $plugin->vendor) }}" class="flex items-center gap-1.5 hover:text-brand"><x-icon name="store" class="size-4" /> {{ $plugin->vendor->name }}</a>
                            <a href="#reviews"><x-rating :value="$plugin->rating_average" :count="$plugin->reviews_count" /></a>
                            <span class="flex items-center gap-1.5"><x-icon name="download" class="size-4" /> {{ number_format($plugin->downloads_count, 0, ',', '.') }} Downloads</span>
                        </div>
                    </div>
                </header>

                @if ($plugin->screenshots->isNotEmpty())
                    <section class="mt-8" x-data="gallery(@js($plugin->screenshots->map(fn ($shot) => ['url' => $shot->url(), 'caption' => $shot->caption])->all()))" x-on:keydown.escape.window="close()">
                        <div class="flex snap-x gap-3 overflow-x-auto pb-2">
                            @foreach ($plugin->screenshots as $shot)
                                <button type="button" class="shrink-0 snap-start overflow-hidden rounded-lg border border-line transition hover:border-brand" x-on:click="open({{ $loop->index }})">
                                    <img src="{{ $shot->url() }}" alt="{{ $shot->caption ?? 'Screenshot '.$loop->iteration }}" loading="lazy" class="h-44 w-auto object-cover sm:h-56">
                                </button>
                            @endforeach
                        </div>
                        <div x-cloak x-show="index !== null" x-transition.opacity class="fixed inset-0 z-[70] flex items-center justify-center bg-black/90 p-4" x-on:click.self="close()">
                            <button type="button" class="absolute top-4 right-4 text-white/80 hover:text-white" x-on:click="close()" aria-label="Schließen"><x-icon name="x" class="size-7" /></button>
                            <button type="button" class="absolute left-4 text-white/80 hover:text-white" x-on:click="step(-1)" aria-label="Zurück"><x-icon name="chevron-left" class="size-9" /></button>
                            <figure class="max-h-full max-w-5xl">
                                <img :src="index !== null ? images[index].url : ''" alt="" class="max-h-[82vh] rounded-lg">
                                <figcaption class="mt-3 text-center text-sm text-white/70" x-text="index !== null ? images[index].caption : ''"></figcaption>
                            </figure>
                            <button type="button" class="absolute right-4 text-white/80 hover:text-white" x-on:click="step(1)" aria-label="Weiter"><x-icon name="chevron-right" class="size-9" /></button>
                        </div>
                    </section>
                @endif

                <div class="mt-8" x-data="{ tab: window.location.hash === '#reviews' ? 'reviews' : 'description' }">
                    <div class="flex gap-1 overflow-x-auto border-b border-line" role="tablist">
                        @foreach (['description' => 'Beschreibung', 'changelog' => 'Changelog ('.$plugin->releases->count().')', 'reviews' => 'Bewertungen ('.$plugin->reviews_count.')'] as $key => $label)
                            <button type="button" role="tab" class="border-b-2 px-4 py-3 font-display text-sm font-semibold whitespace-nowrap transition"
                                    :class="tab === '{{ $key }}' ? 'border-brand text-brand' : 'border-transparent text-mist hover:text-snow'"
                                    x-on:click="tab = '{{ $key }}'">{{ $label }}</button>
                        @endforeach
                    </div>

                    <section x-show="tab === 'description'" class="pt-6">
                        @if (filled($plugin->description))
                            <x-markdown :content="$plugin->description" />
                        @else
                            <p class="text-sm text-mist">Für dieses Plugin wurde noch keine ausführliche Beschreibung hinterlegt.</p>
                        @endif
                        @if ($plugin->tags->isNotEmpty())
                            <div class="mt-8 flex flex-wrap gap-2">
                                @foreach ($plugin->tags as $tag)
                                    <a href="{{ route('plugins.index', ['tag' => $tag->slug]) }}" class="rounded-full border border-line px-3 py-1 text-xs text-mist transition hover:border-brand hover:text-brand">#{{ $tag->name }}</a>
                                @endforeach
                            </div>
                        @endif
                    </section>

                    <section x-cloak x-show="tab === 'changelog'" class="space-y-4 pt-6">
                        @forelse ($plugin->releases as $release)
                            <article class="card p-5">
                                <div class="flex flex-wrap items-center gap-3">
                                    <h3 class="font-display text-lg font-bold">v{{ $release->version }}</h3>
                                    @if ($loop->first)
                                        <x-badge tone="brand">Aktuell</x-badge>
                                    @endif
                                    <span class="text-xs text-mist">{{ $release->published_at?->format('d.m.Y') }} · {{ $release->humanFileSize() }} · {{ number_format($release->downloads_count, 0, ',', '.') }} Downloads</span>
                                    @can('download', $plugin)
                                        <a href="{{ route('downloads.start', $release) }}" class="btn btn-secondary btn-sm ml-auto"><x-icon name="download" class="size-4" /> Download</a>
                                    @endcan
                                </div>
                                @if ($release->platforms->isNotEmpty() || filled($release->game_versions))
                                    <div class="mt-3 flex flex-wrap gap-1.5">
                                        @foreach ($release->platforms as $platform)
                                            <x-badge tone="sky">{{ $platform->name }}</x-badge>
                                        @endforeach
                                        @foreach ($release->game_versions ?? [] as $gameVersion)
                                            <x-badge>{{ $gameVersion }}</x-badge>
                                        @endforeach
                                    </div>
                                @endif
                                @if (filled($release->changelog))
                                    <x-markdown :content="$release->changelog" class="mt-4 text-sm" />
                                @endif
                                <p class="mt-3 font-mono text-[0.7rem] break-all text-mist/60">SHA-256: {{ $release->file_hash }}</p>
                            </article>
                        @empty
                            <p class="text-sm text-mist">Noch keine veröffentlichten Versionen.</p>
                        @endforelse
                    </section>

                    <section x-cloak x-show="tab === 'reviews'" id="reviews" class="scroll-mt-24 pt-6">
                        <div class="card mb-6 grid gap-6 p-6 sm:grid-cols-[12rem_minmax(0,1fr)]">
                            <div class="text-center">
                                <p class="font-display text-5xl font-extrabold text-brand">{{ number_format($plugin->rating_average, 1, ',', '.') }}</p>
                                <x-rating :value="$plugin->rating_average" class="mt-2" size="size-5" />
                                <p class="mt-1 text-xs text-mist">{{ $plugin->reviews_count }} {{ $plugin->reviews_count === 1 ? 'Bewertung' : 'Bewertungen' }}</p>
                            </div>
                            <div class="space-y-1.5">
                                @foreach ([5, 4, 3, 2, 1] as $stars)
                                    @php($share = $plugin->reviews_count > 0 ? ((int) ($ratingBreakdown[$stars] ?? 0)) / $plugin->reviews_count * 100 : 0)
                                    <div class="flex items-center gap-3 text-xs text-mist">
                                        <span class="w-3">{{ $stars }}</span>
                                        <x-icon name="star" class="size-3.5 fill-amber-400 text-amber-400" />
                                        <span class="h-2 flex-1 overflow-hidden rounded-full bg-ink-950"><span class="block h-full rounded-full bg-amber-400" style="width: {{ round($share) }}%"></span></span>
                                        <span class="w-8 text-right">{{ (int) ($ratingBreakdown[$stars] ?? 0) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        @if ($canReview)
                            <form method="POST" action="{{ route('plugins.reviews.store', $plugin) }}" class="card mb-6 space-y-4 p-6" x-data="{ rating: {{ (int) old('rating', $ownReview?->rating ?? 5) }} }">
                                @csrf
                                <h3 class="font-semibold">{{ $ownReview ? 'Deine Bewertung bearbeiten' : 'Bewertung schreiben' }}</h3>
                                <div>
                                    <span class="label">Sterne</span>
                                    <input type="hidden" name="rating" :value="rating">
                                    <div class="flex gap-1">
                                        @for ($star = 1; $star <= 5; $star++)
                                            <button type="button" x-on:click="rating = {{ $star }}" :class="rating >= {{ $star }} ? 'text-amber-400' : 'text-line'" aria-label="{{ $star }} Sterne">
                                                <x-icon name="star" class="size-7" ::class="rating >= {{ $star }} && 'fill-current'" />
                                            </button>
                                        @endfor
                                    </div>
                                </div>
                                <x-input label="Titel (optional)" name="title" :value="$ownReview?->title" maxlength="120" />
                                <x-textarea label="Dein Erfahrungsbericht" name="body" :value="$ownReview?->body" rows="4" required minlength="10" maxlength="3000" />
                                <button type="submit" class="btn btn-primary btn-sm">Bewertung speichern</button>
                            </form>
                        @elseif (auth()->guest())
                            <p class="mb-6 text-sm text-mist"><a href="{{ route('login') }}" class="text-brand hover:underline">Melde dich an</a>, um dieses Plugin zu bewerten.</p>
                        @endif

                        <div class="space-y-4">
                            @forelse ($reviews as $review)
                                <article class="card p-5">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <span class="flex size-9 items-center justify-center rounded-full bg-brand/10 font-display font-bold text-brand">{{ mb_strtoupper(mb_substr($review->user->name, 0, 1)) }}</span>
                                        <div>
                                            <p class="text-sm font-semibold">{{ $review->user->name }}</p>
                                            <p class="text-xs text-mist">{{ $review->created_at->format('d.m.Y') }} · Verifizierter Besitzer</p>
                                        </div>
                                        <x-rating :value="$review->rating" class="ml-auto" />
                                        @can('delete', $review)
                                            <form method="POST" action="{{ route('reviews.destroy', $review) }}" data-confirm="Bewertung wirklich löschen?">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-mist hover:text-red-400" title="Löschen"><x-icon name="trash-2" class="size-4" /></button>
                                            </form>
                                        @endcan
                                    </div>
                                    @if ($review->title)
                                        <h4 class="mt-3 font-semibold">{{ $review->title }}</h4>
                                    @endif
                                    <p class="mt-2 text-sm leading-relaxed whitespace-pre-line text-mist">{{ $review->body }}</p>

                                    @if ($review->vendor_reply)
                                        <div class="mt-4 rounded border-l-2 border-brand bg-ink-950 px-4 py-3">
                                            <p class="text-xs font-semibold text-brand">Antwort von {{ $plugin->vendor->name }}</p>
                                            <p class="mt-1 text-sm whitespace-pre-line text-mist">{{ $review->vendor_reply }}</p>
                                        </div>
                                    @elseif (auth()->user()?->ownsVendor($plugin->vendor))
                                        <form method="POST" action="{{ route('vendor.reviews.reply', $review) }}" class="mt-4 flex gap-2">
                                            @csrf
                                            <input type="text" name="reply" class="input" placeholder="Als Studio antworten …" required maxlength="2000">
                                            <button type="submit" class="btn btn-secondary btn-sm">Antworten</button>
                                        </form>
                                    @endif
                                </article>
                            @empty
                                <p class="text-sm text-mist">Noch keine Bewertungen – sei der Erste!</p>
                            @endforelse
                        </div>

                        {{ $reviews->fragment('reviews')->links() }}
                    </section>
                </div>
            </div>

            <aside class="space-y-6">
                <div class="card p-6 lg:sticky lg:top-24">
                    <x-price :quote="$quote" class="text-3xl" />
                    @if ($quote->isDiscounted())
                        <x-badge tone="amber" class="mt-2">{{ $quote->sale->name }} · -{{ rtrim(rtrim(number_format($quote->percent(), 1, ',', ''), '0'), ',') }}%</x-badge>
                    @endif
                    <p class="mt-1 text-xs text-mist">{{ $plugin->isFree() ? 'Dauerhaft kostenlos' : 'inkl. MwSt. · '.$plugin->license_model->label() }}</p>

                    <div class="mt-5 space-y-2.5">
                        @if ($license?->isUsable())
                            <div class="flex items-center gap-2 rounded border border-brand/30 bg-brand/10 px-3 py-2.5 text-sm text-brand">
                                <x-icon name="badge-check" class="size-4" /> Du besitzt eine gültige Lizenz
                            </div>
                            @if ($plugin->releases->isNotEmpty())
                                <a href="{{ route('downloads.start', $plugin->releases->first()) }}" class="btn btn-primary w-full"><x-icon name="download" class="size-4" /> v{{ $plugin->releases->first()->version }} herunterladen</a>
                            @endif
                            <a href="{{ route('account.licenses.index') }}" class="btn btn-secondary w-full"><x-icon name="key-round" class="size-4" /> Lizenz verwalten</a>
                        @elseif (! $plugin->isPublished())
                            <p class="text-sm text-mist">Kauf und Download sind nach der Freigabe verfügbar.</p>
                        @elseif ($plugin->isFree())
                            @if ($plugin->releases->isNotEmpty())
                                <a href="{{ route('downloads.start', $plugin->releases->first()) }}" class="btn btn-primary w-full"><x-icon name="download" class="size-4" /> Kostenlos herunterladen</a>
                            @else
                                <p class="text-sm text-mist">Noch keine Version verfügbar.</p>
                            @endif
                        @elseif ($inCart)
                            <a href="{{ route('cart.show') }}" class="btn btn-primary w-full"><x-icon name="shopping-cart" class="size-4" /> Zum Warenkorb</a>
                        @else
                            <form method="POST" action="{{ route('cart.store', $plugin) }}">
                                @csrf
                                <button type="submit" class="btn btn-primary w-full"><x-icon name="shopping-cart" class="size-4" /> {{ $license?->isExpired() ? 'Lizenz verlängern' : 'In den Warenkorb' }}</button>
                            </form>
                        @endif

                        @if ($plugin->isPublished())
                            <button type="button" class="btn btn-secondary w-full" x-data="favorite('{{ route('plugins.favorite', $plugin) }}', @js($favorited))" x-on:click="toggle()">
                                <x-icon name="heart" class="size-4" ::class="active && 'fill-red-400 text-red-400'" />
                                <span x-text="active ? 'Auf der Merkliste' : 'Merken'"></span>
                            </button>
                        @endif
                    </div>

                    <dl class="mt-6 space-y-3 border-t border-line pt-5 text-sm">
                        <div class="flex justify-between gap-4"><dt class="text-mist">Aktuelle Version</dt><dd class="font-semibold">{{ $plugin->latest_version ? 'v'.$plugin->latest_version : '–' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-mist">Aktualisiert</dt><dd>{{ $plugin->releases->first()?->published_at?->format('d.m.Y') ?? '–' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-mist">Veröffentlicht</dt><dd>{{ $plugin->published_at?->format('d.m.Y') ?? '–' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-mist">Kategorie</dt><dd><a href="{{ route('plugins.index', ['category' => $plugin->category->slug]) }}" class="hover:text-brand">{{ $plugin->category->name }}</a></dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-mist">Produkt-ID</dt><dd><code class="kbd">{{ $plugin->public_id }}</code></dd></div>
                    </dl>

                    <div class="mt-5 border-t border-line pt-5">
                        <h3 class="label">Kompatibilität</h3>
                        <div class="flex flex-wrap gap-1.5">
                            @forelse ($plugin->platforms as $platform)
                                <a href="{{ route('plugins.index', ['platform' => $platform->slug]) }}"><x-badge tone="sky">{{ $platform->name }}</x-badge></a>
                            @empty
                                <span class="text-sm text-mist">Keine Angabe</span>
                            @endforelse
                            @foreach ($plugin->releases->first()?->game_versions ?? [] as $gameVersion)
                                <x-badge>{{ $gameVersion }}</x-badge>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-5 border-t border-line pt-5">
                        <h3 class="label">Lizenzmodell</h3>
                        <p class="flex items-center gap-2 text-sm font-semibold"><x-icon name="scale" class="size-4 text-brand" /> {{ $plugin->license_model->label() }}</p>
                        <p class="mt-1 text-xs leading-relaxed text-mist">{{ $plugin->license_model->description() }} Aktivierung auf bis zu {{ $settings->maxIps() }} IP-Adressen gleichzeitig.</p>
                    </div>
                </div>
            </aside>
        </div>

        @if ($related->isNotEmpty())
            <section class="mt-16">
                <h2 class="section-title mb-6">Ähnliche Plugins</h2>
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($related as $relatedPlugin)
                        <x-plugin-card :plugin="$relatedPlugin" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-layouts.app>
