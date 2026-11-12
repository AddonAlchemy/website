@if ($paginator->hasPages())
    <nav data-pagination class="mt-8 flex items-center justify-between" role="navigation" aria-label="Seitennavigation">
        @if ($paginator->onFirstPage())
            <span class="btn btn-secondary btn-sm opacity-40">Zurück</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn btn-secondary btn-sm">Zurück</a>
        @endif

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn btn-secondary btn-sm">Weiter</a>
        @else
            <span class="btn btn-secondary btn-sm opacity-40">Weiter</span>
        @endif
    </nav>
@endif
