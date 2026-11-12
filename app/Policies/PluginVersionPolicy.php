<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PluginVersion;
use App\Models\User;

final class PluginVersionPolicy
{
    public function delete(User $user, PluginVersion $version): bool
    {
        return $user->ownsVendor($version->plugin->vendor);
    }

    public function download(User $user, PluginVersion $version): bool
    {
        if ($user->ownsVendor($version->plugin->vendor)) {
            return true;
        }

        return $version->isApproved() && $user->can('download', $version->plugin);
    }
}
