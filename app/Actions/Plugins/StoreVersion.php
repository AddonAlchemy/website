<?php

declare(strict_types=1);

namespace App\Actions\Plugins;

use App\Enums\PluginStatus;
use App\Exceptions\InvalidReleaseArchive;
use App\Models\Plugin;
use App\Models\PluginVersion;
use App\Services\KeyGenerator;
use App\Services\Notifier;
use App\Services\ReleaseArchiveInspector;
use App\Services\ReleaseStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

final class StoreVersion
{
    public function __construct(
        private readonly ReleaseArchiveInspector $inspector,
        private readonly ReleaseStorage $storage,
        private readonly KeyGenerator $keys,
        private readonly Notifier $notifier,
    ) {
    }

    /**
     * @param  array{version: string, changelog?: ?string, game_versions?: list<string>, platforms?: list<int>}  $data
     *
     * @throws InvalidReleaseArchive
     */
    public function handle(Plugin $plugin, array $data, UploadedFile $archive): PluginVersion
    {
        $inspected = $this->inspector->inspect($archive);
        $path = $this->storage->store($archive, $plugin);

        try {
            $version = DB::transaction(function () use ($plugin, $data, $archive, $inspected, $path): PluginVersion {
                $version = $plugin->versions()->create([
                    'public_id' => $this->keys->versionId(),
                    'version' => $data['version'],
                    'changelog' => $data['changelog'] ?? null,
                    'game_versions' => array_values($data['game_versions'] ?? []),
                    'file_path' => $path,
                    'file_name' => ReleaseStorage::sanitizeFileName($archive->getClientOriginalName()),
                    'file_size' => $inspected->sizeBytes,
                    'file_hash' => $inspected->sha256,
                ]);

                $version->platforms()->sync($data['platforms'] ?? []);

                return $version;
            });
        } catch (Throwable $exception) {
            $this->storage->delete($path);

            throw $exception;
        }

        if ($plugin->status === PluginStatus::Approved) {
            $this->notifier->admins(
                'Neue Version zur Prüfung',
                $plugin->name.' '.$version->version.' wartet auf Freigabe.',
                'file-archive',
                route('admin.moderation.index'),
            );
        }

        return $version;
    }
}
