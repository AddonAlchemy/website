<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Settlement;
use App\Models\User;

final class SettlementPolicy
{
    public function view(User $user, Settlement $settlement): bool
    {
        return $user->vendor?->id === $settlement->vendor_id;
    }
}
