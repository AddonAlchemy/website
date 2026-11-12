<x-layouts.app title="Plugin-Katalog" description="Durchsuche den gesamten Plugin-Katalog nach Kategorie, Plattform, Preis und Bewertung.">
    <div class="container-page py-10" x-data="catalog">
        <div class="mb-8">
            <h1 class="text-3xl font-bold tracking-tight">Plugin-<span class="text-brand">Katalog</span></h1>
            <p class="mt-1 text-sm text-mist">Entdecke geprüfte Plugins, Erweiterungen und Integrationen.</p>
        </div>

        <div class="lg:grid lg:grid-cols-[17rem_minmax(0,1fr)] lg:gap-8">
            <aside>
                <button type="button" class="btn btn-secondary btn-sm mb-4 w-full lg:hidden" x-on:click="filtersOpen = !filtersOpen">
                    <x-icon name="sliders-horizontal" class="size-4" /> Filter
                </button>

                <form x-ref="form" action="{{ route('plugins.index') }}" method="GET" class="card hidden space-y-6 p-5 lg:sticky lg:top-24 lg:block"
                      x-bind:class="filtersOpen && '!block'" x-on:submit.prevent="queue()">
                    <div>
                        <label for="q" class="label">Suche</label>
                        <div class="relative">
                            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-mist" />
                            <input type="search" id="q" name="q" value="{{ $filters->search }}" placeholder="Name, Studio, Tag …" class="input pl-9" x-on:input="queue(300)">
                        </div>
                    </div>

                    <div>
                        <label for="category" class="label">Kategorie</label>
                        <select id="category" name="category" class="input" x-on:change="queue()">
                            <option value="">Alle Kategorien</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->slug }}" @selected($filters->category === $category->slug)>{{ $category->name }} ({{ $category->plugins_count }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="platform" class="label">Kompatibilität</label>
                        <select id="platform" name="platform" class="input" x-on:change="queue()">
                            <option value="">Alle Plattformen</option>
                            @foreach ($platforms as $platform)
                                <option value="{{ $platform->slug }}" @selected($filters->platform === $platform->slug)>{{ $platform->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <fieldset>
                        <legend class="label">Preis</legend>
                        <div class="space-y-2 text-sm">
                            @foreach (['' => 'Alle', 'free' => 'Kostenlos', 'paid' => 'Kostenpflichtig'] as $value => $label)
                                <label class="flex items-center gap-2.5">
                                    <input type="radio" name="price" value="{{ $value }}" class="checkbox !rounded-full" @checked(($filters->price ?? '') === $value) x-on:change="queue()"> {{ $label }}
                                </label>
                            @endforeach
                        </div>
                        <div class="mt-3">
                            <label for="max_price" class="sr-only">Maximalpreis</label>
                            <input type="number" id="max_price" name="max_price" min="0" step="1" placeholder="Max. Preis in {{ $settings->get('currency') }}" class="input"
                                   value="{{ $filters->maxPriceCents !== null ? $filters->maxPriceCents / 100 : '' }}" x-on:input="queue(400)">
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend class="label">Bewertung</legend>
                        <div class="space-y-2 text-sm">
                            <label class="flex items-center gap-2.5">
                                <input type="radio" name="rating" value="" class="checkbox !rounded-full" @checked($filters->minRating === null) x-on:change="queue()"> Alle
                            </label>
                            @foreach ([4, 3, 2] as $rating)
                                <label class="flex items-center gap-2.5">
                                    <input type="radio" name="rating" value="{{ $rating }}" class="checkbox !rounded-full" @checked($filters->minRating === $rating) x-on:change="queue()">
                                    <x-rating :value="$rating" /> <span class="text-mist">&amp; mehr</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <div>
                        <label for="downloads" class="label">Downloads</label>
                        <select id="downloads" name="downloads" class="input" x-on:change="queue()">
                            <option value="">Beliebig</option>
                            @foreach ([100 => '100+', 1000 => '1.000+', 10000 => '10.000+'] as $value => $label)
                                <option value="{{ $value }}" @selected($filters->minDownloads === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if ($tags->isNotEmpty())
                        <div>
                            <p class="label">Beliebte Tags</p>
                            <input type="hidden" name="tag" value="{{ $filters->tag }}" x-ref="tag" x-bind:value="tag">
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($tags as $tag)
                                    <button type="button" class="rounded-full border px-2.5 py-1 text-xs transition"
                                            :class="tag === '{{ $tag->slug }}' ? 'border-brand text-brand' : 'border-line text-mist hover:border-brand hover:text-brand'"
                                            x-on:click="toggleTag('{{ $tag->slug }}')">#{{ $tag->name }}</button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <input type="hidden" name="sort" value="{{ $filters->sort }}" x-ref="sort">
                    <button type="button" class="btn btn-secondary btn-sm w-full" x-on:click="reset()"><x-icon name="rotate-ccw" class="size-4" /> Filter zurücksetzen</button>
                </form>
            </aside>

            <div class="min-w-0">
                <div class="mb-5 flex flex-wrap items-center justify-end gap-3">
                    <label for="sort-select" class="text-xs tracking-wider text-mist uppercase">Sortierung</label>
                    <select id="sort-select" class="input !w-auto !py-2" x-on:change="$refs.sort.value = $event.target.value; queue()">
                        @foreach (\App\Data\CatalogFilters::SORTS as $value => $label)
                            <option value="{{ $value }}" @selected($filters->sort === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div x-ref="results" class="scroll-mt-24 transition-opacity duration-150" :class="loading && 'opacity-50'" aria-live="polite">
                    @include('storefront.plugins._results')
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
