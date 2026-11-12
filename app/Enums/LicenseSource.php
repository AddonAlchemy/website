<?php

declare(strict_types=1);

namespace App\Enums;

enum LicenseSource: string
{
    case Purchase = 'purchase';
    case Free = 'free';
    case Code = 'code';
    case Grant = 'grant';

    public function label(): string
    {
        return match ($this) {
            self::Purchase => 'Kauf',
            self::Free => 'Gratis-Produkt',
            self::Code => 'Lizenz-Code',
            self::Grant => 'Zuweisung',
        };
    }
}
