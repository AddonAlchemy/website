<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Platform;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PlatformSeeder extends Seeder
{
    private const PLATFORMS = [
        'Paper', 'Spigot', 'Purpur', 'Bukkit', 'Folia', 'Fabric', 'Forge', 'NeoForge',
        'Velocity', 'BungeeCord', 'Standalone', 'Web', 'Docker',
    ];

    public function run(): void
    {
        foreach (self::PLATFORMS as $position => $name) {
            Platform::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'position' => $position + 1],
            );
        }
    }
}
