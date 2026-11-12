<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Plugin;

final readonly class CartLine
{
    public function __construct(
        public Plugin $plugin,
        public PriceQuote $quote,
    ) {
    }
}
