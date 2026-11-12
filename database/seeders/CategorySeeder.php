<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    private const CATEGORIES = [
        ['Administration', 'shield', 'Team-Verwaltung, Rechte, Logs und Moderations-Werkzeuge.'],
        ['Wirtschaft & Shops', 'coins', 'Währungen, Auktionshäuser, Shops und Belohnungssysteme.'],
        ['Minigames & Events', 'gamepad-2', 'Spielmodi, Arenen und automatisierte Events.'],
        ['Welt & Building', 'map', 'Welt-Generierung, Schutzzonen und Bau-Werkzeuge.'],
        ['Anti-Cheat & Schutz', 'shield-alert', 'Cheat-Erkennung, Exploit-Fixes und Zugriffsschutz.'],
        ['Chat & Community', 'message-square', 'Chat-Formatierung, Ränge, Tickets und Umfragen.'],
        ['Discord-Integrationen', 'plug', 'Synchronisation, Benachrichtigungen und Server-Anbindung.'],
        ['Web & API', 'globe', 'Web-Panels, REST-Schnittstellen und Automatisierung.'],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $position => [$name, $icon, $description]) {
            Category::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'icon' => $icon, 'description' => $description, 'position' => $position + 1],
            );
        }
    }
}
