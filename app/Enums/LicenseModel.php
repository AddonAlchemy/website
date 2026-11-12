<?php

declare(strict_types=1);

namespace App\Enums;

use Carbon\CarbonInterface;

enum LicenseModel: string
{
    case Perpetual = 'perpetual';
    case Annual = 'annual';

    public function label(): string
    {
        return match ($this) {
            self::Perpetual => 'Dauerlizenz',
            self::Annual => 'Jahreslizenz',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Perpetual => 'Einmalzahlung, unbegrenzte Laufzeit inklusive aller Updates.',
            self::Annual => '12 Monate Nutzung, Updates und Aktivierungen. Verlängerung durch erneuten Kauf.',
        };
    }

    public function expiresFrom(CarbonInterface $start): ?CarbonInterface
    {
        return match ($this) {
            self::Perpetual => null,
            self::Annual => $start->copy()->addYear(),
        };
    }
}
