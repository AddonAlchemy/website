<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\VersionStatus;
use App\Models\Plugin;
use App\Models\PluginVersion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PluginVersion>
 */
class PluginVersionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'public_id' => 'VER-'.Str::upper(Str::random(4)).'-'.Str::upper(Str::random(4)),
            'plugin_id' => Plugin::factory(),
            'version' => fake()->unique()->numerify('#.#.##'),
            'changelog' => '- '.fake()->sentence(),
            'game_versions' => ['1.21'],
            'status' => VersionStatus::Approved,
            'file_path' => 'releases/'.Str::uuid().'.zip',
            'file_name' => 'release.zip',
            'file_size' => fake()->numberBetween(20_000, 900_000),
            'file_hash' => hash('sha256', Str::random(32)),
            'published_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => VersionStatus::Pending, 'published_at' => null]);
    }
}
