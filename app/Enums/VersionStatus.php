<?php

declare(strict_types=1);

namespace App\Enums;

enum VersionStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'In Prüfung',
            self::Approved => 'Freigegeben',
            self::Rejected => 'Abgelehnt',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Approved => 'brand',
            self::Rejected => 'red',
        };
    }
}
