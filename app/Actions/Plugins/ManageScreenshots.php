<?php

declare(strict_types=1);

namespace App\Actions\Plugins;

use App\Exceptions\MarketplaceException;
use App\Models\Plugin;
use App\Models\PluginScreenshot;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class ManageScreenshots
{
    /**
     * @param  list<UploadedFile>  $images
     *
     * @throws MarketplaceException
     */
    public function add(Plugin $plugin, array $images, ?string $caption = null): void
    {
        $limit = (int) config('marketplace.media.max_screenshots');
        $existing = $plugin->screenshots()->count();

        if ($existing + count($images) > $limit) {
            throw new MarketplaceException('Pro Plugin sind maximal '.$limit.' Screenshots möglich.');
        }

        $position = (int) $plugin->screenshots()->max('position');

        foreach ($images as $image) {
            $plugin->screenshots()->create([
                'path' => Storage::disk(config('marketplace.media.disk'))->putFile('plugins/screenshots', $image),
                'caption' => $caption,
                'position' => ++$position,
            ]);
        }
    }

    public function remove(PluginScreenshot $screenshot): void
    {
        $screenshot->delete();

        Storage::disk(config('marketplace.media.disk'))->delete($screenshot->path);
    }
}
