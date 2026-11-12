<?php

declare(strict_types=1);

namespace App\Data;

final readonly class PaymentMethod
{
    public function __construct(
        public string $id,
        public string $label,
        public float $percent,
        public int $fixedCents,
    ) {
    }

    /**
     * @param  array{id: string, label: string, percent?: float|int|string, fixed_cents?: int|string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            label: $data['label'],
            percent: (float) ($data['percent'] ?? 0),
            fixedCents: (int) ($data['fixed_cents'] ?? 0),
        );
    }
}
