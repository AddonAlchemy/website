<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\CatalogRequest;
use App\Models\Category;
use App\Models\Platform;
use App\Models\Plugin;
use App\Models\Tag;
use App\Queries\PluginCatalogQuery;
use App\Services\Cart;
use App\Services\FavoriteRegistry;
use App\Services\PriceCalculator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class PluginController extends Controller
{
    private const PER_PAGE = 12;

    public function index(CatalogRequest $request, PluginCatalogQuery $catalog): View
    {
        $filters = $request->filters();
        $plugins = $catalog->build($filters)->paginate(self::PER_PAGE)->withQueryString();

        if ($request->ajax()) {
            return view('storefront.plugins._results', ['plugins' => $plugins, 'filters' => $filters]);
        }

        return view('storefront.plugins.index', [
            'plugins' => $plugins,
            'filters' => $filters,
            'categories' => Category::query()
                ->withCount(['plugins' => static fn ($query) => $query->published()])
                ->orderBy('position')
                ->get(),
            'platforms' => Platform::query()->orderBy('position')->get(),
            'tags' => Tag::query()
                ->withCount(['plugins' => static fn ($query) => $query->published()])
                ->orderByDesc('plugins_count')
                ->limit(14)
                ->get()
                ->where('plugins_count', '>', 0),
        ]);
    }

    public function show(
        Request $request,
        Plugin $plugin,
        PriceCalculator $prices,
        Cart $cart,
        FavoriteRegistry $favorites,
    ): View {
        $this->authorize('view', $plugin);

        $plugin->load([
            'vendor',
            'category',
            'tags',
            'platforms',
            'screenshots',
            'releases' => static fn ($query) => $query->with('platforms')->limit(25),
        ]);

        $user = $request->user();

        return view('storefront.plugins.show', [
            'plugin' => $plugin,
            'quote' => $prices->quote($plugin),
            'reviews' => $plugin->reviews()->with('user:id,name')->latest()->paginate(8)->withQueryString(),
            'ratingBreakdown' => $plugin->reviews()->toBase()
                ->selectRaw('rating, COUNT(*) as total')->groupBy('rating')->pluck('total', 'rating'),
            'related' => Plugin::query()->published()
                ->where('category_id', $plugin->category_id)
                ->whereKeyNot($plugin->id)
                ->with(['vendor:id,name,slug', 'category:id,name,slug,icon', 'platforms'])
                ->orderByDesc('downloads_count')
                ->limit(3)
                ->get(),
            'license' => $user?->licenses()->where('plugin_id', $plugin->id)->first(),
            'ownReview' => $user?->reviews()->where('plugin_id', $plugin->id)->first(),
            'canReview' => $user?->can('review', $plugin) ?? false,
            'inCart' => $cart->has($plugin),
            'favorited' => $favorites->contains($plugin),
        ]);
    }
}
