<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Fulfilled = 'fulfilled';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'In Bearbeitung',
            self::Fulfilled => 'Abgeschlossen',
            self::Cancelled => 'Storniert',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Fulfilled => 'brand',
            self::Cancelled => 'slate',
        };
    }
}
