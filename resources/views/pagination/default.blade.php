@if ($paginator->hasPages())
    <nav data-pagination class="mt-8 flex flex-wrap items-center justify-center gap-1.5" role="navigation" aria-label="Seitennavigation">
        @if ($paginator->onFirstPage())
            <span class="rounded border border-line px-3 py-1.5 text-sm text-mist/40"><x-icon name="chevron-left" class="size-4" /></span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="rounded border border-line px-3 py-1.5 text-sm text-mist transition hover:border-brand hover:text-brand" aria-label="Zurück"><x-icon name="chevron-left" class="size-4" /></a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="px-2 text-sm text-mist">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page" class="rounded border border-brand bg-brand px-3 py-1.5 font-display text-sm font-bold text-black">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="rounded border border-line px-3 py-1.5 font-display text-sm text-mist transition hover:border-brand hover:text-brand">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="rounded border border-line px-3 py-1.5 text-sm text-mist transition hover:border-brand hover:text-brand" aria-label="Weiter"><x-icon name="chevron-right" class="size-4" /></a>
        @else
            <span class="rounded border border-line px-3 py-1.5 text-sm text-mist/40"><x-icon name="chevron-right" class="size-4" /></span>
        @endif
    </nav>
@endif
