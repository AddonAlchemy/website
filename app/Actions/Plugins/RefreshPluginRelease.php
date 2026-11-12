<?php

declare(strict_types=1);

namespace App\Actions\Plugins;

use App\Models\Plugin;

final class RefreshPluginRelease
{
    public function handle(Plugin $plugin): void
    {
        $latest = $plugin->releases()->with('platforms:id')->first();

        $plugin->update(['latest_version' => $latest?->version]);
        $plugin->platforms()->sync($latest?->platforms->modelKeys() ?? []);
    }
}
