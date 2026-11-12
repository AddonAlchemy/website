<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\PluginVersion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PluginVersion
 */
final class PluginVersionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'version' => $this->version,
            'changelog' => $this->changelog,
            'platforms' => $this->whenLoaded('platforms', fn () => $this->platforms->pluck('name')),
            'game_versions' => $this->game_versions ?? [],
            'file' => [
                'name' => $this->file_name,
                'size' => $this->file_size,
                'sha256' => $this->file_hash,
            ],
            'downloads' => $this->downloads_count,
            'published_at' => $this->published_at?->toIso8601String(),
        ];
    }
}
