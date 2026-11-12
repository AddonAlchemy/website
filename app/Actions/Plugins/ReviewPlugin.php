<?php

declare(strict_types=1);

namespace App\Actions\Plugins;

use App\Enums\PluginStatus;
use App\Enums\VersionStatus;
use App\Models\Plugin;
use App\Models\User;
use App\Services\Notifier;
use Illuminate\Support\Facades\DB;

final class ReviewPlugin
{
    public function __construct(
        private readonly ReviewVersion $reviewVersion,
        private readonly Notifier $notifier,
    ) {
    }

    public function approve(Plugin $plugin, User $reviewer): Plugin
    {
        return DB::transaction(function () use ($plugin, $reviewer): Plugin {
            $plugin->update([
                'status' => PluginStatus::Approved,
                'rejection_reason' => null,
                'published_at' => $plugin->published_at ?? now(),
            ]);

            $plugin->versions()
                ->where('status', VersionStatus::Pending)
                ->get()
                ->each(fn ($version) => $this->reviewVersion->approve($version->setRelation('plugin', $plugin), $reviewer, notify: false));

            $this->notifier->vendor(
                $plugin->vendor,
                'Plugin veröffentlicht',
                $plugin->name.' wurde geprüft und ist jetzt im Marktplatz sichtbar.',
                'badge-check',
                route('plugins.show', $plugin),
            );

            return $plugin;
        });
    }

    public function reject(Plugin $plugin, string $reason): Plugin
    {
        $plugin->update(['status' => PluginStatus::Rejected, 'rejection_reason' => $reason]);

        $this->notifier->vendor(
            $plugin->vendor,
            'Plugin abgelehnt',
            $plugin->name.' wurde nicht freigegeben. Grund: '.$reason,
            'circle-x',
            route('vendor.plugins.edit', $plugin),
        );

        return $plugin;
    }
}
