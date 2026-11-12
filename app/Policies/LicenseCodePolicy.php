<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LicenseCode;
use App\Models\User;

final class LicenseCodePolicy
{
    public function update(User $user, LicenseCode $code): bool
    {
        return $user->vendor?->id === $code->vendor_id;
    }
}
