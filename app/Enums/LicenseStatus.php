<?php

declare(strict_types=1);

namespace App\Enums;

enum LicenseStatus: string
{
    case Active = 'active';
    case Revoked = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktiv',
            self::Revoked => 'Widerrufen',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Active => 'brand',
            self::Revoked => 'red',
        };
    }
}
