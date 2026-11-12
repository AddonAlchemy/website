<?php

declare(strict_types=1);

namespace App\Actions\Plugins;

use App\Enums\PluginStatus;
use App\Exceptions\MarketplaceException;
use App\Models\Plugin;
use App\Services\Notifier;

final class SubmitPlugin
{
    public function __construct(private readonly Notifier $notifier)
    {
    }

    /**
     * @throws MarketplaceException
     */
    public function handle(Plugin $plugin): Plugin
    {
        if (! $plugin->status->isEditableSubmission()) {
            throw new MarketplaceException('Dieses Plugin wurde bereits eingereicht.');
        }

        if (! $plugin->versions()->exists()) {
            throw new MarketplaceException('Lade mindestens eine Version hoch, bevor du das Plugin einreichst.');
        }

        $plugin->update(['status' => PluginStatus::Pending, 'rejection_reason' => null]);

        $this->notifier->admins(
            'Plugin zur Prüfung eingereicht',
            $plugin->name.' wartet in der Moderations-Queue.',
            'shield-check',
            route('admin.moderation.index'),
        );

        return $plugin;
    }
}
