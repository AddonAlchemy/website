<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\CatalogRequest;
use App\Http\Resources\PluginResource;
use App\Http\Resources\PluginVersionResource;
use App\Models\Plugin;
use App\Queries\PluginCatalogQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class CatalogController extends Controller
{
    public function index(CatalogRequest $request, PluginCatalogQuery $catalog): AnonymousResourceCollection
    {
        return PluginResource::collection(
            $catalog->build($request->filters())->with('tags')->paginate(20)->withQueryString(),
        );
    }

    public function show(Plugin $plugin): PluginResource
    {
        abort_unless($plugin->isPublished(), 404);

        return new PluginResource($plugin->load(['vendor', 'category', 'platforms', 'tags', 'latestRelease.platforms']));
    }

    public function versions(Plugin $plugin): AnonymousResourceCollection
    {
        abort_unless($plugin->isPublished(), 404);

        return PluginVersionResource::collection($plugin->releases()->with('platforms')->paginate(25));
    }
}
