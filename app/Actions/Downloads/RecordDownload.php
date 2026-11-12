<?php

declare(strict_types=1);

namespace App\Actions\Downloads;

use App\Models\Download;
use App\Models\Plugin;
use App\Models\PluginVersion;
use Illuminate\Support\Facades\DB;

final class RecordDownload
{
    public function handle(PluginVersion $version, ?int $userId, ?int $licenseId, ?string $ip): void
    {
        DB::transaction(static function () use ($version, $userId, $licenseId, $ip): void {
            Download::query()->create([
                'user_id' => $userId,
                'plugin_id' => $version->plugin_id,
                'plugin_version_id' => $version->id,
                'license_id' => $licenseId,
                'ip' => $ip,
            ]);

            $version->increment('downloads_count');
            Plugin::query()->whereKey($version->plugin_id)->increment('downloads_count');
        });
    }
}
