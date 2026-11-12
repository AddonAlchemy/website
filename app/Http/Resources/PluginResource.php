<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Plugin;
use App\Services\PriceCalculator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Plugin
 */
final class PluginResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $quote = app(PriceCalculator::class)->quote($this->resource);

        return [
            'id' => $this->public_id,
            'slug' => $this->slug,
            'name' => $this->name,
            'summary' => $this->summary,
            'description' => $this->when($request->routeIs('api.plugins.show'), $this->description),
            'category' => $this->whenLoaded('category', fn () => $this->category->only(['name', 'slug'])),
            'vendor' => $this->whenLoaded('vendor', fn () => $this->vendor->only(['name', 'slug'])),
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->pluck('name')),
            'platforms' => $this->whenLoaded('platforms', fn () => $this->platforms->pluck('name')),
            'license_model' => $this->license_model->value,
            'price' => [
                'amount' => $quote->finalCents,
                'original_amount' => $quote->originalCents,
                'discount_percent' => $quote->percent(),
                'currency' => 'EUR',
                'formatted' => money($quote->finalCents),
            ],
            'rating' => ['average' => $this->rating_average, 'count' => $this->reviews_count],
            'downloads' => $this->downloads_count,
            'latest_version' => $this->latest_version,
            'latest_release' => new PluginVersionResource($this->whenLoaded('latestRelease')),
            'icon_url' => $this->iconUrl(),
            'url' => route('plugins.show', $this->resource),
            'published_at' => $this->published_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
