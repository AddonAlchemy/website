<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

final class ReviewPolicy
{
    public function delete(User $user, Review $review): bool
    {
        return $review->user_id === $user->id;
    }

    public function reply(User $user, Review $review): bool
    {
        return $user->ownsVendor($review->plugin->vendor);
    }
}
