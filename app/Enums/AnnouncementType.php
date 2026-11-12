<?php

declare(strict_types=1);

namespace App\Enums;

enum AnnouncementType: string
{
    case Info = 'info';
    case News = 'news';
    case Update = 'update';
    case Sale = 'sale';
    case Promotion = 'promotion';
    case Event = 'event';
    case Warning = 'warning';
    case Maintenance = 'maintenance';

    public function label(): string
    {
        return match ($this) {
            self::Info => 'Info',
            self::News => 'Neuigkeit',
            self::Update => 'Update',
            self::Sale => 'Sale',
            self::Promotion => 'Aktion',
            self::Event => 'Event',
            self::Warning => 'Warnung',
            self::Maintenance => 'Wartung',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Info => '#5865F2',
            self::News => '#88C040',
            self::Update => '#00bcd4',
            self::Sale => '#ffaa00',
            self::Promotion => '#e91e63',
            self::Event => '#9c27b0',
            self::Warning => '#ff5252',
            self::Maintenance => '#9e9e9e',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Info => 'info',
            self::News => 'megaphone',
            self::Update => 'refresh-cw',
            self::Sale => 'tag',
            self::Promotion => 'ticket',
            self::Event => 'party-popper',
            self::Warning => 'triangle-alert',
            self::Maintenance => 'construction',
        };
    }

    public function isBanner(): bool
    {
        return in_array($this, [self::Sale, self::Promotion, self::Event], true);
    }

    /**
     * @return list<string>
     */
    public static function bannerValues(): array
    {
        return array_map(
            static fn (self $type): string => $type->value,
            array_values(array_filter(self::cases(), static fn (self $type): bool => $type->isBanner())),
        );
    }
}
