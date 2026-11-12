<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\CartLine;
use App\Models\Plugin;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;

final class Cart
{
    private const SESSION_KEY = 'cart.plugins';

    /** @var Collection<int, CartLine>|null */
    private ?Collection $lines = null;

    public function __construct(
        private readonly Session $session,
        private readonly PriceCalculator $prices,
    ) {
    }

    public function add(Plugin $plugin): void
    {
        $this->store([...$this->ids(), $plugin->id]);
    }

    public function remove(Plugin $plugin): void
    {
        $this->store(array_diff($this->ids(), [$plugin->id]));
    }

    public function clear(): void
    {
        $this->store([]);
    }

    public function has(Plugin $plugin): bool
    {
        return in_array($plugin->id, $this->ids(), true);
    }

    public function count(): int
    {
        return count($this->ids());
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    /**
     * @return list<int>
     */
    public function ids(): array
    {
        return array_values(array_map('intval', (array) $this->session->get(self::SESSION_KEY, [])));
    }

    /**
     * @return Collection<int, CartLine>
     */
    public function lines(): Collection
    {
        if ($this->lines !== null) {
            return $this->lines;
        }

        $plugins = $this->isEmpty()
            ? new Collection()
            : Plugin::query()->published()->with(['vendor:id,name,slug', 'category:id,name,slug,icon'])->findMany($this->ids());

        if ($plugins->count() !== $this->count()) {
            $this->store($plugins->modelKeys());
        }

        return $this->lines = $plugins
            ->map(fn (Plugin $plugin): CartLine => new CartLine($plugin, $this->prices->quote($plugin)))
            ->values();
    }

    public function totalCents(): int
    {
        return (int) $this->lines()->sum(static fn (CartLine $line): int => $line->quote->finalCents);
    }

    public function originalTotalCents(): int
    {
        return (int) $this->lines()->sum(static fn (CartLine $line): int => $line->quote->originalCents);
    }

    /**
     * @param  array<int, int>  $ids
     */
    private function store(array $ids): void
    {
        $this->session->put(self::SESSION_KEY, array_values(array_unique($ids)));
        $this->lines = null;
    }
}
