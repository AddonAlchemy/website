<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Data\PriceQuote;
use App\Models\Plugin;
use App\Services\FavoriteRegistry;
use App\Services\PriceCalculator;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

final class PluginCard extends Component
{
    public PriceQuote $quote;

    public bool $favorited;

    public function __construct(
        public Plugin $plugin,
        PriceCalculator $prices,
        FavoriteRegistry $favorites,
    ) {
        $this->quote = $prices->quote($plugin);
        $this->favorited = $favorites->contains($plugin);
    }

    public function render(): View
    {
        return view('components.plugin-card');
    }
}
