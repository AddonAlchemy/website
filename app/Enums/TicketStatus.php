<?php

declare(strict_types=1);

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case Answered = 'answered';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Offen',
            self::Answered => 'Beantwortet',
            self::Closed => 'Geschlossen',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Open => 'amber',
            self::Answered => 'brand',
            self::Closed => 'slate',
        };
    }
}
