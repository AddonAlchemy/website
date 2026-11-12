<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LicenseSource;
use App\Enums\LicenseStatus;
use App\Models\License;
use App\Models\Plugin;
use App\Models\User;
use App\Services\KeyGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<License>
 */
class LicenseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => app(KeyGenerator::class)->make('AALC', 4, 4),
            'user_id' => User::factory(),
            'plugin_id' => Plugin::factory(),
            'source' => LicenseSource::Purchase,
            'status' => LicenseStatus::Active,
        ];
    }

    public function revoked(): static
    {
        return $this->state(['status' => LicenseStatus::Revoked, 'revoked_at' => now()]);
    }

    public function expired(): static
    {
        return $this->state(['expires_at' => now()->subDay()]);
    }
}
