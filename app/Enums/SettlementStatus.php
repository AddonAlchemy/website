<?php

declare(strict_types=1);

namespace App\Enums;

enum SettlementStatus: string
{
    case Pending = 'pending';
    case Available = 'available';
    case PaidOut = 'paid_out';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'In Bearbeitung',
            self::Available => 'Im Guthaben',
            self::PaidOut => 'Ausgezahlt',
            self::Cancelled => 'Storniert',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Available => 'brand',
            self::PaidOut => 'sky',
            self::Cancelled => 'slate',
        };
    }

    public function vendorNotice(): string
    {
        return match ($this) {
            self::Pending => 'wird wieder bearbeitet',
            self::Available => 'wurde freigegeben und deinem Guthaben gutgeschrieben',
            self::PaidOut => 'wurde ausgezahlt',
            self::Cancelled => 'wurde storniert',
        };
    }
}
