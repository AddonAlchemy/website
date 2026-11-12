<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LicenseModel;
use App\Enums\PluginStatus;
use App\Models\Category;
use App\Models\Plugin;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Plugin>
 */
class PluginFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(2, true));

        return [
            'public_id' => 'PRD-'.Str::upper(Str::random(4)).'-'.Str::upper(Str::random(4)),
            'vendor_id' => Vendor::factory(),
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 99999),
            'summary' => fake()->sentence(12),
            'description' => "## Features\n\n- ".implode("\n- ", fake()->sentences(4)),
            'price_cents' => fake()->randomElement([499, 799, 999, 1499, 2499]),
            'license_model' => LicenseModel::Perpetual,
            'status' => PluginStatus::Approved,
            'published_at' => now(),
        ];
    }

    public function free(): static
    {
        return $this->state(['price_cents' => 0]);
    }

    public function draft(): static
    {
        return $this->state(['status' => PluginStatus::Draft, 'published_at' => null]);
    }

    public function pending(): static
    {
        return $this->state(['status' => PluginStatus::Pending, 'published_at' => null]);
    }

    public function annual(): static
    {
        return $this->state(['license_model' => LicenseModel::Annual]);
    }
}
