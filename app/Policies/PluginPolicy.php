<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Plugin;
use App\Models\User;

final class PluginPolicy
{
    public function view(?User $user, Plugin $plugin): bool
    {
        return $plugin->isPublished() || ($user !== null && $user->ownsVendor($plugin->vendor));
    }

    public function create(User $user): bool
    {
        return (bool) $user->vendor?->isApproved();
    }

    public function update(User $user, Plugin $plugin): bool
    {
        return $user->ownsVendor($plugin->vendor);
    }

    public function delete(User $user, Plugin $plugin): bool
    {
        return $user->ownsVendor($plugin->vendor);
    }

    public function download(User $user, Plugin $plugin): bool
    {
        if ($user->ownsVendor($plugin->vendor)) {
            return true;
        }

        return ($plugin->isPublished() && $plugin->isFree()) || $user->hasUsableLicenseFor($plugin);
    }

    public function review(User $user, Plugin $plugin): bool
    {
        return ! $user->ownsVendor($plugin->vendor)
            && $user->licenses()->where('plugin_id', $plugin->id)->exists();
    }
}
