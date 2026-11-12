<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\License;
use App\Models\PluginVersion;
use App\Models\User;
use Illuminate\Support\Facades\URL;

final class DownloadLinkFactory
{
    public function make(PluginVersion $version, ?User $user = null, ?License $license = null): string
    {
        return URL::temporarySignedRoute(
            'downloads.file',
            now()->addSeconds(config('marketplace.downloads.link_ttl_seconds')),
            array_filter([
                'version' => $version->public_id,
                'u' => $user?->id ?? $license?->user_id,
                'l' => $license?->id,
            ]),
        );
    }
}
