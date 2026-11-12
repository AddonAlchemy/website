<?php

declare(strict_types=1);

namespace App\Actions\Licensing;

use App\Data\LicenseApiResult;
use App\Models\License;
use App\Models\LicenseSubkey;
use App\Models\Plugin;
use App\Models\PluginVersion;
use App\Services\DownloadLinkFactory;
use Illuminate\Database\Eloquent\Builder;

final class CheckForUpdate
{
    public function __construct(private readonly DownloadLinkFactory $links)
    {
    }

    /**
     * @param  array{product_id: string, version?: ?string, key?: ?string, platform?: ?string}  $input
     */
    public function handle(array $input): LicenseApiResult
    {
        $plugin = Plugin::query()->published()->where('public_id', $input['product_id'])->first();

        if ($plugin === null) {
            return new LicenseApiResult(404, ['status' => 'error', 'reason' => 'unknown_product']);
        }

        /** @var PluginVersion|null $latest */
        $latest = $plugin->releases()
            ->with('platforms:id,name,slug')
            ->when($input['platform'] ?? null, static fn (Builder $query, string $platform) => $query
                ->whereHas('platforms', static fn (Builder $p) => $p->where('slug', $platform)))
            ->first();

        if ($latest === null) {
            return new LicenseApiResult(404, ['status' => 'error', 'reason' => 'no_release']);
        }

        $current = $input['version'] ?? null;
        $license = $this->resolveLicense($plugin, $input['key'] ?? null);
        $entitled = $plugin->isFree() || $license !== null;

        return LicenseApiResult::ok([
            'status' => 'ok',
            'product_id' => $plugin->public_id,
            'product' => $plugin->name,
            'current_version' => $current,
            'latest_version' => $latest->version,
            'update_available' => $current === null || version_compare($latest->version, $current, '>'),
            'released_at' => $latest->published_at?->toIso8601String(),
            'changelog' => $latest->changelog,
            'platforms' => $latest->platforms->pluck('name')->all(),
            'game_versions' => $latest->game_versions ?? [],
            'file' => [
                'name' => $latest->file_name,
                'size' => $latest->file_size,
                'sha256' => $latest->file_hash,
            ],
            'entitled' => $entitled,
            'download_url' => $entitled ? $this->links->make($latest, license: $license) : null,
        ]);
    }

    private function resolveLicense(Plugin $plugin, ?string $key): ?License
    {
        if (blank($key)) {
            return null;
        }

        $license = License::query()->usable()->where('key', $key)->where('plugin_id', $plugin->id)->first();

        if ($license !== null) {
            return $license;
        }

        $subkey = LicenseSubkey::query()
            ->where('key', $key)
            ->whereHas('plugins', static fn (Builder $query) => $query->whereKey($plugin->id))
            ->first();

        if ($subkey === null || ! $subkey->isActive()) {
            return null;
        }

        return License::query()->usable()->where('user_id', $subkey->user_id)->where('plugin_id', $plugin->id)->first();
    }
}
