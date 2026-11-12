<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\PriceQuote;
use App\Enums\SaleScope;
use App\Models\Plugin;
use App\Models\Sale;
use Illuminate\Support\Collection;

final class PriceCalculator
{
    /** @var Collection<int, Sale>|null */
    private ?Collection $runningSales = null;

    public function quote(Plugin $plugin): PriceQuote
    {
        $original = $plugin->price_cents;
        $sale = $original > 0 ? $this->bestSaleFor($plugin) : null;

        if ($sale === null) {
            return new PriceQuote($original, $original);
        }

        $final = max(0, (int) round($original * (1 - min(100.0, max(0.0, $sale->percent)) / 100)));

        return new PriceQuote($original, $final, $sale);
    }

    public function flush(): void
    {
        $this->runningSales = null;
    }

    private function bestSaleFor(Plugin $plugin): ?Sale
    {
        return $this->runningSales()
            ->filter(static fn (Sale $sale): bool => $sale->appliesTo($plugin))
            ->sort(static fn (Sale $a, Sale $b): int => [$b->scope === SaleScope::Vendor, $b->percent] <=> [$a->scope === SaleScope::Vendor, $a->percent])
            ->first();
    }

    /**
     * @return Collection<int, Sale>
     */
    private function runningSales(): Collection
    {
        return $this->runningSales ??= Sale::query()->running()->get();
    }
}
