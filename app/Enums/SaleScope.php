<?php

declare(strict_types=1);

namespace App\Enums;

enum SaleScope: string
{
    case Shop = 'shop';
    case Vendor = 'vendor';

    public function label(): string
    {
        return match ($this) {
            self::Shop => 'Gesamter Shop',
            self::Vendor => 'Einzelnes Studio',
        };
    }
}
