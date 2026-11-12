<?php

declare(strict_types=1);

namespace App\Actions\Plugins;

use App\Models\PluginVersion;
use App\Services\ReleaseStorage;
use Illuminate\Support\Facades\DB;

final class DeleteVersion
{
    public function __construct(
        private readonly ReleaseStorage $storage,
        private readonly RefreshPluginRelease $refreshRelease,
    ) {
    }

    public function handle(PluginVersion $version): void
    {
        DB::transaction(function () use ($version): void {
            $version->delete();
            $this->refreshRelease->handle($version->plugin);
        });

        $this->storage->delete($version->file_path);
    }
}
