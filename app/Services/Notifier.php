<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\ActivityNotification;
use Illuminate\Support\Facades\Notification;

final class Notifier
{
    public function user(User $user, string $title, string $body, string $icon = 'bell', ?string $url = null): void
    {
        $user->notify(new ActivityNotification($title, $body, $icon, $url));
    }

    public function vendor(Vendor $vendor, string $title, string $body, string $icon = 'bell', ?string $url = null): void
    {
        $owner = $vendor->relationLoaded('user') ? $vendor->user : $vendor->user()->first();

        if ($owner !== null) {
            $this->user($owner, $title, $body, $icon, $url);
        }
    }

    public function admins(string $title, string $body, string $icon = 'bell', ?string $url = null): void
    {
        Notification::send(
            User::query()->where('role', UserRole::Admin)->get(),
            new ActivityNotification($title, $body, $icon, $url),
        );
    }
}
