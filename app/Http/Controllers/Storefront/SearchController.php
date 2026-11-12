<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Data\CatalogFilters;
use App\Http\Controllers\Controller;
use App\Models\Plugin;
use App\Queries\PluginCatalogQuery;
use App\Services\PriceCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SearchController extends Controller
{
    public function __invoke(Request $request, PluginCatalogQuery $catalog, PriceCalculator $prices): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        if (mb_strlen($term) < 2 || mb_strlen($term) > 80) {
            return response()->json(['results' => []]);
        }

        $results = $catalog->build(new CatalogFilters(search: $term, sort: 'downloads'))
            ->limit(6)
            ->get()
            ->map(static fn (Plugin $plugin): array => [
                'name' => $plugin->name,
                'summary' => $plugin->summary,
                'vendor' => $plugin->vendor->name,
                'category' => $plugin->category->name,
                'icon' => $plugin->iconUrl(),
                'price' => $prices->quote($plugin)->isFree() ? 'Gratis' : money($prices->quote($plugin)->finalCents),
                'url' => route('plugins.show', $plugin),
            ]);

        return response()->json([
            'results' => $results,
            'more' => route('plugins.index', ['q' => $term]),
        ]);
    }
}
