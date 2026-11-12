<?php

declare(strict_types=1);

namespace App\Data;

final readonly class SettlementBreakdown
{
    public function __construct(
        public int $units,
        public int $grossCents,
        public int $vendorDiscountCents,
        public int $platformDiscountCents,
        public int $paymentFeeCents,
        public int $platformFeeCents,
    ) {
    }

    public function netCents(): int
    {
        return $this->grossCents - $this->vendorDiscountCents - $this->paymentFeeCents - $this->platformFeeCents;
    }
}
