<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Sale;

final readonly class PriceQuote
{
    public function __construct(
        public int $originalCents,
        public int $finalCents,
        public ?Sale $sale = null,
    ) {
    }

    public function discountCents(): int
    {
        return $this->originalCents - $this->finalCents;
    }

    public function isDiscounted(): bool
    {
        return $this->sale !== null && $this->discountCents() > 0;
    }

    public function percent(): float
    {
        return $this->sale?->percent ?? 0.0;
    }

    public function isFree(): bool
    {
        return $this->finalCents === 0;
    }
}
