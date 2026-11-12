<?php

declare(strict_types=1);

namespace App\Actions\Plugins;

use App\Enums\VersionStatus;
use App\Models\PluginVersion;
use App\Models\User;
use App\Services\Notifier;
use Illuminate\Support\Facades\DB;

final class ReviewVersion
{
    public function __construct(
        private readonly RefreshPluginRelease $refreshRelease,
        private readonly Notifier $notifier,
    ) {
    }

    public function approve(PluginVersion $version, User $reviewer, bool $notify = true): PluginVersion
    {
        return DB::transaction(function () use ($version, $reviewer, $notify): PluginVersion {
            $version->update([
                'status' => VersionStatus::Approved,
                'rejection_reason' => null,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'published_at' => $version->published_at ?? now(),
            ]);

            $this->refreshRelease->handle($version->plugin);

            if ($notify) {
                $this->notifier->vendor(
                    $version->plugin->vendor,
                    'Version freigegeben',
                    $version->plugin->name.' '.$version->version.' steht jetzt zum Download bereit.',
                    'badge-check',
                    route('vendor.plugins.edit', $version->plugin),
                );
            }

            return $version;
        });
    }

    public function reject(PluginVersion $version, User $reviewer, string $reason): PluginVersion
    {
        $version->update([
            'status' => VersionStatus::Rejected,
            'rejection_reason' => $reason,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        $this->notifier->vendor(
            $version->plugin->vendor,
            'Version abgelehnt',
            $version->plugin->name.' '.$version->version.' wurde nicht freigegeben. Grund: '.$reason,
            'circle-x',
            route('vendor.plugins.edit', $version->plugin),
        );

        return $version;
    }
}
