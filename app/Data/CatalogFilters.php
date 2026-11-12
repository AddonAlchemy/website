<?php

declare(strict_types=1);

namespace App\Data;

final readonly class CatalogFilters
{
    public const SORTS = [
        'featured' => 'Empfohlen',
        'newest' => 'Neueste',
        'updated' => 'Zuletzt aktualisiert',
        'downloads' => 'Meiste Downloads',
        'rating' => 'Beste Bewertung',
        'price_asc' => 'Preis aufsteigend',
        'price_desc' => 'Preis absteigend',
    ];

    public function __construct(
        public ?string $search = null,
        public ?string $category = null,
        public ?string $tag = null,
        public ?string $platform = null,
        public ?string $price = null,
        public ?int $maxPriceCents = null,
        public ?int $minRating = null,
        public ?int $minDownloads = null,
        public string $sort = 'featured',
    ) {
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            search: filled($input['q'] ?? null) ? trim((string) $input['q']) : null,
            category: $input['category'] ?? null,
            tag: $input['tag'] ?? null,
            platform: $input['platform'] ?? null,
            price: $input['price'] ?? null,
            maxPriceCents: isset($input['max_price']) ? (int) round(((float) $input['max_price']) * 100) : null,
            minRating: isset($input['rating']) ? (int) $input['rating'] : null,
            minDownloads: isset($input['downloads']) ? (int) $input['downloads'] : null,
            sort: $input['sort'] ?? 'featured',
        );
    }

    public function isFiltered(): bool
    {
        return $this->search !== null
            || $this->category !== null
            || $this->tag !== null
            || $this->platform !== null
            || $this->price !== null
            || $this->maxPriceCents !== null
            || $this->minRating !== null
            || $this->minDownloads !== null;
    }
}
