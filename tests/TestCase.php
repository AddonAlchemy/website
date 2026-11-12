<?php

declare(strict_types=1);

namespace Tests;

use App\Models\Category;
use App\Models\Plugin;
use App\Models\PluginVersion;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

abstract class TestCase extends BaseTestCase
{
    protected function approvedVendor(): Vendor
    {
        return Vendor::factory()->create();
    }

    protected function admin(): User
    {
        return User::factory()->admin()->create();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function publishedPlugin(array $attributes = [], string $version = '1.0.0'): Plugin
    {
        $plugin = Plugin::factory()->create($attributes + ['category_id' => Category::factory()]);

        $this->releaseFor($plugin, $version);

        return $plugin->refresh();
    }

    protected function releaseFor(Plugin $plugin, string $version = '1.0.0'): PluginVersion
    {
        $path = 'releases/'.$plugin->public_id.'/'.$version.'.zip';
        Storage::disk(config('marketplace.releases.disk'))->put($path, 'archive-contents');

        $release = PluginVersion::factory()->create([
            'plugin_id' => $plugin->id,
            'version' => $version,
            'file_path' => $path,
        ]);

        $plugin->update(['latest_version' => $version]);

        return $release;
    }

    /**
     * @param  array<string, string>  $entries
     */
    protected function zipUpload(array $entries = ['plugin.yml' => "name: Demo\n"], string $name = 'release.zip'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'zip');
        $archive = new ZipArchive();
        $archive->open($path, ZipArchive::OVERWRITE);

        foreach ($entries as $entry => $contents) {
            $archive->addFromString($entry, $contents);
        }

        $archive->close();

        return new UploadedFile($path, $name, 'application/zip', null, true);
    }
}
