<?php

declare(strict_types=1);

namespace App\Enums;

enum PluginStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Entwurf',
            self::Pending => 'In Prüfung',
            self::Approved => 'Veröffentlicht',
            self::Rejected => 'Abgelehnt',
            self::Archived => 'Archiviert',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Draft, self::Archived => 'slate',
            self::Pending => 'amber',
            self::Approved => 'brand',
            self::Rejected => 'red',
        };
    }

    public function isEditableSubmission(): bool
    {
        return in_array($this, [self::Draft, self::Rejected], true);
    }
}
