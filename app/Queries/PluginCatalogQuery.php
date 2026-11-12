<?php

declare(strict_types=1);

namespace App\Queries;

use App\Data\CatalogFilters;
use App\Models\Plugin;
use Illuminate\Database\Eloquent\Builder;

final class PluginCatalogQuery
{
    /**
     * @return Builder<Plugin>
     */
    public function build(CatalogFilters $filters): Builder
    {
        $query = Plugin::query()
            ->published()
            ->with(['vendor:id,name,slug', 'category:id,name,slug,icon', 'platforms']);

        $this->applySearch($query, $filters->search);

        $query
            ->when($filters->category, static fn (Builder $q, string $slug) => $q
                ->whereHas('category', static fn (Builder $c) => $c->where('slug', $slug)))
            ->when($filters->tag, static fn (Builder $q, string $slug) => $q
                ->whereHas('tags', static fn (Builder $t) => $t->where('slug', $slug)))
            ->when($filters->platform, static fn (Builder $q, string $slug) => $q
                ->whereHas('platforms', static fn (Builder $p) => $p->where('slug', $slug)))
            ->when($filters->price === 'free', static fn (Builder $q) => $q->where('price_cents', 0))
            ->when($filters->price === 'paid', static fn (Builder $q) => $q->where('price_cents', '>', 0))
            ->when($filters->maxPriceCents !== null, static fn (Builder $q) => $q->where('price_cents', '<=', $filters->maxPriceCents))
            ->when($filters->minRating, static fn (Builder $q, int $rating) => $q->where('rating_average', '>=', $rating))
            ->when($filters->minDownloads, static fn (Builder $q, int $downloads) => $q->where('downloads_count', '>=', $downloads));

        return $this->applySort($query, $filters->sort);
    }

    /**
     * @param  Builder<Plugin>  $query
     */
    private function applySearch(Builder $query, ?string $search): void
    {
        if ($search === null) {
            return;
        }

        $term = '%'.addcslashes($search, '%_\\').'%';

        $query->where(static fn (Builder $q) => $q
            ->whereAny(['plugins.name', 'plugins.summary', 'plugins.public_id'], 'like', $term)
            ->orWhereHas('vendor', static fn (Builder $v) => $v->where('name', 'like', $term))
            ->orWhereHas('tags', static fn (Builder $t) => $t->where('name', 'like', $term)));
    }

    /**
     * @param  Builder<Plugin>  $query
     * @return Builder<Plugin>
     */
    private function applySort(Builder $query, string $sort): Builder
    {
        return match ($sort) {
            'newest' => $query->latest('published_at'),
            'updated' => $query->latest('updated_at'),
            'downloads' => $query->orderByDesc('downloads_count'),
            'rating' => $query->orderByDesc('rating_average')->orderByDesc('reviews_count'),
            'price_asc' => $query->orderBy('price_cents'),
            'price_desc' => $query->orderByDesc('price_cents'),
            default => $query->orderByDesc('is_featured')->orderByDesc('downloads_count'),
        };
    }
}
