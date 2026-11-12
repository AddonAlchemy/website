<?php

declare(strict_types=1);

namespace App\Actions\Plugins;

use App\Enums\PluginStatus;
use App\Models\OrderItem;
use App\Models\Plugin;
use App\Services\ReleaseStorage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class RemovePlugin
{
    public function __construct(private readonly ReleaseStorage $storage)
    {
    }

    /**
     * @return bool true when the plugin was deleted, false when it was archived instead
     */
    public function handle(Plugin $plugin): bool
    {
        $hasCustomers = $plugin->licenses()->exists()
            || OrderItem::query()->where('plugin_id', $plugin->id)->exists();

        if ($hasCustomers) {
            $plugin->update(['status' => PluginStatus::Archived, 'is_featured' => false]);

            return false;
        }

        $releaseFiles = $plugin->versions()->pluck('file_path');
        $media = $plugin->screenshots()->pluck('path')->push($plugin->icon_path)->filter();

        DB::transaction(static fn () => $plugin->delete());

        $releaseFiles->each(fn (string $path) => $this->storage->delete($path));
        Storage::disk(config('marketplace.media.disk'))->delete($media->all());

        return true;
    }
}
