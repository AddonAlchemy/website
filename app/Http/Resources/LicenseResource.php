<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\License;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin License
 */
final class LicenseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'status' => $this->isExpired() ? 'expired' : $this->status->value,
            'source' => $this->source->value,
            'product' => [
                'id' => $this->plugin->public_id,
                'name' => $this->plugin->name,
                'latest_version' => $this->plugin->latest_version,
            ],
            'activations' => $this->whenLoaded('activations', fn () => $this->activations->map->only([
                'instance_id', 'ip', 'name', 'last_seen_at',
            ])),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
