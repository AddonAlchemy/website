<?php

declare(strict_types=1);

namespace App\Actions\Plugins;

use App\Models\Plugin;
use App\Models\Tag;
use App\Models\Vendor;
use App\Services\KeyGenerator;
use App\Support\UniqueSlug;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class SavePlugin
{
    public function __construct(private readonly KeyGenerator $keys)
    {
    }

    /**
     * @param  array{name: string, summary: string, description?: ?string, category_id: int, price_cents: int, license_model: string, tags?: list<string>}  $data
     */
    public function create(Vendor $vendor, array $data, ?UploadedFile $icon = null): Plugin
    {
        return DB::transaction(function () use ($vendor, $data, $icon): Plugin {
            $plugin = new Plugin([
                'vendor_id' => $vendor->id,
                'public_id' => $this->keys->pluginId(),
                'slug' => UniqueSlug::for(Plugin::class, $data['name']),
            ]);

            return $this->persist($plugin, $data, $icon);
        });
    }

    /**
     * @param  array{name: string, summary: string, description?: ?string, category_id: int, price_cents: int, license_model: string, tags?: list<string>}  $data
     */
    public function update(Plugin $plugin, array $data, ?UploadedFile $icon = null): Plugin
    {
        return DB::transaction(fn (): Plugin => $this->persist($plugin, $data, $icon));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function persist(Plugin $plugin, array $data, ?UploadedFile $icon): Plugin
    {
        $plugin->fill([
            'name' => $data['name'],
            'summary' => $data['summary'],
            'description' => $data['description'] ?? null,
            'category_id' => $data['category_id'],
            'price_cents' => $data['price_cents'],
            'license_model' => $data['license_model'],
        ]);

        if ($icon !== null) {
            $disk = Storage::disk(config('marketplace.media.disk'));

            if ($plugin->icon_path !== null) {
                $disk->delete($plugin->icon_path);
            }

            $plugin->icon_path = $disk->putFile('plugins/icons', $icon);
        }

        $plugin->save();
        $plugin->tags()->sync($this->resolveTagIds($data['tags'] ?? []));

        return $plugin;
    }

    /**
     * @param  list<string>  $names
     * @return list<int>
     */
    private function resolveTagIds(array $names): array
    {
        return collect($names)
            ->map(static fn (string $name): string => Str::limit(trim($name), 40, ''))
            ->filter()
            ->unique(static fn (string $name): string => Str::slug($name))
            ->take(8)
            ->map(static fn (string $name): int => Tag::query()->firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name],
            )->id)
            ->values()
            ->all();
    }
}
