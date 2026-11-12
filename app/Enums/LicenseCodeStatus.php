<?php

declare(strict_types=1);

namespace App\Enums;

enum LicenseCodeStatus: string
{
    case Unused = 'unused';
    case Redeemed = 'redeemed';
    case Revoked = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::Unused => 'Unbenutzt',
            self::Redeemed => 'Eingelöst',
            self::Revoked => 'Widerrufen',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Unused => 'sky',
            self::Redeemed => 'brand',
            self::Revoked => 'red',
        };
    }
}
