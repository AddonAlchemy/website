<?php

declare(strict_types=1);

namespace App\Enums;

enum VendorStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'In Prüfung',
            self::Approved => 'Freigeschaltet',
            self::Rejected => 'Abgelehnt',
            self::Suspended => 'Gesperrt',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Approved => 'brand',
            self::Rejected, self::Suspended => 'red',
        };
    }
}
