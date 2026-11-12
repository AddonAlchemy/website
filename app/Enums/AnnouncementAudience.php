<?php

declare(strict_types=1);

namespace App\Enums;

enum AnnouncementAudience: string
{
    case All = 'all';
    case Customers = 'customers';
    case Vendors = 'vendors';

    public function label(): string
    {
        return match ($this) {
            self::All => 'Alle Besucher',
            self::Customers => 'Nur Kunden',
            self::Vendors => 'Nur Vendoren',
        };
    }

    /**
     * @return list<self>
     */
    public static function visibleTo(?UserRole $role): array
    {
        return match ($role) {
            UserRole::Admin => self::cases(),
            UserRole::Vendor => [self::All, self::Vendors],
            UserRole::Customer => [self::All, self::Customers],
            null => [self::All],
        };
    }
}
