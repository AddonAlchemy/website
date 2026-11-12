<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LicenseSubkey;
use App\Models\User;

final class LicenseSubkeyPolicy
{
    public function update(User $user, LicenseSubkey $subkey): bool
    {
        return $subkey->user_id === $user->id;
    }
}
