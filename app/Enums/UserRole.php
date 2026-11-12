<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Customer = 'customer';
    case Vendor = 'vendor';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Customer => 'Kunde',
            self::Vendor => 'Vendor',
            self::Admin => 'Administrator',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Customer => 'slate',
            self::Vendor => 'sky',
            self::Admin => 'violet',
        };
    }
}
