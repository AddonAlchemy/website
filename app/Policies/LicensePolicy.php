<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\License;
use App\Models\User;

final class LicensePolicy
{
    public function update(User $user, License $license): bool
    {
        return $license->user_id === $user->id;
    }
}
