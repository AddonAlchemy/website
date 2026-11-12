<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Checkout\FulfillOrder;
use App\Actions\Licensing\IssueLicense;
use App\Actions\Licensing\ManageLicenseCodes;
use App\Actions\Licensing\ManageSubkeys;
use App\Actions\Plugins\RefreshPluginRelease;
use App\Actions\Reviews\RefreshPluginRating;
use App\Actions\Support\OpenTicket;
use App\Actions\Support\ReplyToTicket;
use App\Enums\AnnouncementAudience;
use App\Enums\AnnouncementType;
use App\Enums\LicenseModel;
use App\Enums\LicenseSource;
use App\Enums\PluginStatus;
use App\Enums\SaleScope;
use App\Enums\SettlementStatus;
use App\Enums\VersionStatus;
use App\Models\Announcement;
use App\Models\Category;
use App\Models\Download;
use App\Models\License;
use App\Models\Platform;
use App\Models\Plugin;
use App\Models\PluginVersion;
use App\Models\Review;
use App\Models\Sale;
use App\Models\Settlement;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vendor;
use App\Services\KeyGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class DemoMarketplaceSeeder extends Seeder
{
    private const VENDORS = [
        ['Media Tech Wilms', 'vendor@addonalchemy.test', 'Werkzeuge für Server-Teams und Communities.'],
        ['Pixelforge Studios', 'hello@pixelforge.test', 'Premium-Plugins für Wirtschaft, Minigames und Web.'],
        ['Nordlicht Software', 'kontakt@nordlicht.test', 'Stabile Kern-Plugins mit Fokus auf Performance.'],
        ['Blockwerk Labs', 'team@blockwerk.test', 'Welt-Generierung, Schutz und Infrastruktur.'],
        ['Runebyte Interactive', 'studio@runebyte.test', 'Kompetitive Spielmodi und Sicherheit.'],
    ];

    private const PLUGINS = [
        ['Staff Manager', 'administration', 0, 599, 'perpetual', 'Schichtplanung, Aktivitäts-Tracking und Beförderungen für dein Server-Team – inklusive Discord-Anbindung.', ['team', 'verwaltung', 'discord'], ['Standalone', 'Docker'], ['Aktivitäts-Tracking mit Wochenberichten', 'Bewerbungs- und Beförderungs-Workflows', 'Abwesenheiten und Schichtplanung']],
        ['PermissionForge', 'administration', 2, 1299, 'perpetual', 'Rollen- und Rechteverwaltung mit Vererbung, Kontexten und Web-Editor.', ['rechte', 'ränge'], ['Paper', 'Spigot', 'Purpur', 'Folia', 'Velocity'], ['Gruppen mit Vererbung und Gewichtung', 'Temporäre Rechte und Kontext-Regeln', 'Web-Editor mit Live-Synchronisation']],
        ['AuditTrail', 'administration', 3, 899, 'annual', 'Lückenlose Protokollierung aller Team-Aktionen mit Rollback-Funktion.', ['logs', 'rollback'], ['Paper', 'Purpur', 'Folia'], ['Block-, Container- und Befehls-Logs', 'Rollback einzelner Spieler oder Regionen', 'Export als CSV und JSON']],
        ['VaultMarket', 'wirtschaft-shops', 1, 1499, 'perpetual', 'Auktionshaus und Spieler-Shops mit Steuern, Gebühren und Statistik-Dashboard.', ['economy', 'shop', 'auktion'], ['Paper', 'Spigot', 'Purpur'], ['Auktionen mit Sofortkauf und Geboten', 'Konfigurierbare Steuern und Gebühren', 'Verkaufsstatistiken je Spieler']],
        ['CoinFlow', 'wirtschaft-shops', 2, 0, 'perpetual', 'Leichtgewichtige Multi-Währungs-Economy mit MySQL-Sync und Vault-Support.', ['economy', 'währung'], ['Paper', 'Spigot', 'Purpur', 'Bukkit', 'Folia'], ['Beliebig viele Währungen', 'Netzwerkweite Synchronisation', 'Vault- und PlaceholderAPI-Support']],
        ['CrateCraft', 'wirtschaft-shops', 4, 999, 'perpetual', 'Animierte Loot-Kisten mit Schlüsseln, Wahrscheinlichkeiten und Vorschau-GUI.', ['crates', 'belohnungen'], ['Paper', 'Spigot', 'Purpur'], ['Zwölf Öffnungs-Animationen', 'Gewichtete Gewinne mit Pity-System', 'Vorschau-Menü für Spieler']],
        ['JobsReloaded Pro', 'wirtschaft-shops', 3, 1199, 'perpetual', 'Berufe-System mit Quests, Leveln und dynamischer Bezahlung.', ['jobs', 'quests', 'economy'], ['Paper', 'Purpur', 'Folia'], ['Über 20 vorkonfigurierte Berufe', 'Tägliche Quests und Boni', 'Dynamische Löhne gegen Inflation']],
        ['BedRush', 'minigames-events', 1, 1999, 'perpetual', 'Komplettes BedWars-Minigame mit Arenen-Editor, Shops und Ranglisten.', ['bedwars', 'minigame', 'pvp'], ['Paper', 'Spigot', 'Purpur'], ['Arenen-Editor mit Auto-Reset', 'Team-Upgrades und Item-Shops', 'Saisonale Ranglisten']],
        ['SkyDuel', 'minigames-events', 4, 1499, 'perpetual', '1vs1- und Team-Duelle mit Kits, Elo-System und Zuschauer-Modus.', ['pvp', 'duelle', 'elo'], ['Paper', 'Purpur'], ['Elo-basiertes Matchmaking', 'Kit-Editor im Spiel', 'Replay- und Zuschauer-Modus']],
        ['EventPilot', 'minigames-events', 2, 799, 'annual', 'Plane automatische Server-Events mit Countdown, Belohnungen und Teleports.', ['events', 'automation'], ['Paper', 'Spigot', 'Purpur', 'Folia'], ['Zeitpläne per Cron-Syntax', 'Belohnungen nach Platzierung', 'Automatische Ankündigungen']],
        ['ParkourLab', 'minigames-events', 3, 0, 'perpetual', 'Parkour-Strecken mit Checkpoints, Bestzeiten und Hologramm-Ranglisten.', ['parkour', 'minigame'], ['Paper', 'Spigot', 'Purpur', 'Bukkit'], ['Strecken-Editor mit Checkpoints', 'Bestzeiten und Ranglisten', 'Hologramm-Anzeigen']],
        ['TerraShape', 'welt-building', 3, 2499, 'perpetual', 'Individuelle Welt-Generierung mit Biom-Editor und Strukturen-Bibliothek.', ['welt', 'generator', 'biome'], ['Paper', 'Purpur', 'Fabric', 'Forge', 'NeoForge'], ['Biom-Editor mit Live-Vorschau', 'Eigene Strukturen und Höhlen', 'Vorgenerierung ohne Lags']],
        ['RegionGuardian', 'welt-building', 2, 1099, 'perpetual', 'Grundstücke und Schutzzonen mit Flags, Mietmodell und Karten-Integration.', ['schutz', 'grundstücke'], ['Paper', 'Spigot', 'Purpur', 'Folia'], ['Über 60 Region-Flags', 'Miet- und Kauf-Grundstücke', 'Integration in Live-Karten']],
        ['SchematicHub', 'welt-building', 1, 699, 'perpetual', 'Verwalte, teile und platziere Schematics direkt im Spiel – mit Vorschau.', ['schematics', 'building'], ['Paper', 'Purpur', 'Fabric'], ['Cloud-Bibliothek für Teams', 'Hologramm-Vorschau vor dem Platzieren', 'Rechte je Schematic']],
        ['SentinelAC', 'anti-cheat-schutz', 4, 2999, 'annual', 'Serverseitiges Anti-Cheat mit über 60 Checks, Replay-Analyse und Webhook-Alerts.', ['anticheat', 'sicherheit'], ['Paper', 'Spigot', 'Purpur', 'Folia'], ['Bewegungs-, Kampf- und Paket-Checks', 'Replay-Analyse für Moderatoren', 'Alerts per Webhook']],
        ['LoginShield', 'anti-cheat-schutz', 2, 0, 'perpetual', 'Schützt Team-Accounts per Zwei-Faktor-Authentifizierung und IP-Bindung.', ['2fa', 'sicherheit'], ['Paper', 'Spigot', 'Purpur', 'Velocity', 'BungeeCord'], ['TOTP-Codes für Team-Mitglieder', 'IP- und Geräte-Bindung', 'Notfall-Codes']],
        ['ExploitPatch', 'anti-cheat-schutz', 3, 899, 'perpetual', 'Schließt bekannte Dupe- und Crash-Exploits und meldet verdächtige Pakete.', ['exploits', 'sicherheit'], ['Paper', 'Purpur', 'Folia'], ['Laufend aktualisierte Exploit-Signaturen', 'Paket-Limiter gegen Crasher', 'Detaillierte Vorfall-Logs']],
        ['ChatStudio', 'chat-community', 1, 799, 'perpetual', 'Chat-Formatierung mit Kanälen, Filtern, Erwähnungen und Emoji-Support.', ['chat', 'formatierung'], ['Paper', 'Spigot', 'Purpur', 'Velocity'], ['Kanäle und private Gruppen', 'Wortfilter mit Regex', 'MiniMessage- und Emoji-Support']],
        ['TicketDesk', 'chat-community', 0, 999, 'perpetual', 'Support-Tickets direkt im Spiel und in Discord – mit Kategorien und Transkripten.', ['support', 'tickets', 'discord'], ['Paper', 'Standalone'], ['Ticket-Kategorien mit Zuständigkeiten', 'Automatische Transkripte', 'Bewertung nach Abschluss']],
        ['PollMaster', 'chat-community', 4, 0, 'perpetual', 'Umfragen und Abstimmungen mit GUI, Zeitlimit und Belohnungen.', ['umfragen', 'community'], ['Paper', 'Spigot', 'Purpur'], ['Umfragen mit Mehrfachauswahl', 'Ergebnisse als Hologramm', 'Belohnungen für Teilnehmer']],
        ['LinkBridge', 'discord-integrationen', 0, 1299, 'perpetual', 'Verknüpft Spieler-Accounts mit Discord, synchronisiert Ränge und Chat.', ['discord', 'sync'], ['Paper', 'Velocity', 'Standalone'], ['Account-Verknüpfung per Code', 'Rollen-Synchronisation in beide Richtungen', 'Chat-Brücke mit Filtern']],
        ['StatusBoard', 'discord-integrationen', 2, 499, 'perpetual', 'Live-Serverstatus, Spielerzahlen und Wartungsmeldungen als Discord-Embeds.', ['discord', 'status'], ['Standalone', 'Docker'], ['Automatisch aktualisierte Embeds', 'Ausfall-Benachrichtigungen', 'Mehrere Server pro Instanz']],
        ['WebPanel X', 'web-api', 1, 3499, 'annual', 'Modernes Web-Panel für Konsole, Dateien, Backups und Spieler-Verwaltung.', ['panel', 'web', 'backups'], ['Web', 'Docker'], ['Live-Konsole im Browser', 'Geplante Backups mit Wiederherstellung', 'Rollen und Zwei-Faktor-Login']],
        ['RestGate', 'web-api', 3, 1499, 'perpetual', 'Sichere REST-API für deinen Server mit Tokens, Rate-Limits und Webhooks.', ['api', 'webhooks'], ['Paper', 'Purpur', 'Velocity'], ['Über 80 Endpunkte', 'Token-Berechtigungen und Rate-Limits', 'Webhooks für Server-Ereignisse']],
    ];

    private const FEATURED = ['Staff Manager', 'VaultMarket', 'BedRush', 'SentinelAC', 'WebPanel X', 'CoinFlow'];

    private const CHANGELOG_NOTES = [
        'Performance bei vielen gleichzeitigen Spielern verbessert',
        'Neue Konfigurationsoptionen für Nachrichten und Sounds',
        'Kompatibilität mit der aktuellen Server-Version hergestellt',
        'Fehler beim Neuladen der Konfiguration behoben',
        'Datenbank-Migration für große Installationen beschleunigt',
        'Zusätzliche Platzhalter für Scoreboards ergänzt',
        'Speicherverbrauch im Leerlauf deutlich reduziert',
        'Übersetzungen aktualisiert und erweitert',
    ];

    private const REVIEWS = [
        [5, 'Läuft absolut stabil', 'Seit Wochen im Einsatz und keinerlei Probleme. Die Einrichtung war in wenigen Minuten erledigt und der Support antwortet schnell.'],
        [5, 'Jeden Cent wert', 'Genau das, was auf unserem Netzwerk gefehlt hat. Die Konfiguration ist sauber dokumentiert und sehr flexibel.'],
        [5, 'Top Support', 'Hatte eine Frage zur Einrichtung und bekam innerhalb einer Stunde eine hilfreiche Antwort. So muss das sein.'],
        [4, 'Sehr gutes Plugin', 'Funktioniert wie beschrieben. Ein paar zusätzliche Platzhalter wären noch schön, ansonsten klare Empfehlung.'],
        [4, 'Solide Umsetzung', 'Macht was es soll und belastet den Server kaum. Updates kommen regelmäßig.'],
        [4, 'Empfehlenswert', 'Gute Performance und durchdachte Funktionen. Die Dokumentation könnte an manchen Stellen ausführlicher sein.'],
        [3, 'Gut, mit Luft nach oben', 'Die Grundfunktionen sind stark, einige Einstellungen sind aber etwas versteckt. Insgesamt zufrieden.'],
        [5, 'Bestes Plugin in der Kategorie', 'Wir haben mehrere Alternativen getestet und sind hier hängen geblieben. Stabil, schnell und laufend gepflegt.'],
        [2, 'Noch nicht ganz rund', 'Bei uns gab es nach dem letzten Update kleinere Probleme. Der Entwickler arbeitet aber bereits an einem Fix.'],
    ];

    public function __construct(
        private readonly FulfillOrder $fulfillOrder,
        private readonly IssueLicense $issueLicense,
        private readonly RefreshPluginRelease $refreshRelease,
        private readonly RefreshPluginRating $refreshRating,
        private readonly ManageSubkeys $subkeys,
        private readonly ManageLicenseCodes $licenseCodes,
        private readonly OpenTicket $openTicket,
        private readonly ReplyToTicket $replyToTicket,
        private readonly KeyGenerator $keys,
    ) {
    }

    public function run(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Admin', 'email' => 'admin@addonalchemy.test']);
        $customer = User::factory()->create(['name' => 'Demo-Kunde', 'email' => 'customer@addonalchemy.test']);
        $customers = User::factory()->count(24)->create()->push($customer);

        $vendors = $this->seedVendors();
        $plugins = $this->seedPlugins($vendors, $admin);

        $this->seedModerationQueue($vendors, $plugins);
        $this->seedSales($vendors);
        $this->seedOrders($customers, $customer, $plugins);
        $this->seedReviews();
        $this->seedDownloads();
        $this->seedLicenseUsage($customer, $vendors->first(), $plugins);
        $this->seedCommunity($admin, $customer, $vendors);
    }

    /**
     * @return Collection<int, Vendor>
     */
    private function seedVendors(): Collection
    {
        $vendors = collect(self::VENDORS)->map(fn (array $data): Vendor => Vendor::factory()->create([
            'user_id' => User::factory()->vendor()->create(['name' => $data[0], 'email' => $data[1]]),
            'name' => $data[0],
            'slug' => Str::slug($data[0]),
            'tagline' => $data[2],
            'bio' => "**{$data[0]}** entwickelt seit Jahren Erweiterungen für Server-Netzwerke und Communities.\n\n- Regelmäßige Updates\n- Deutschsprachiger Support\n- Faire Lizenzmodelle",
            'website' => 'https://'.Str::slug($data[0]).'.example',
            'support_email' => 'support@'.Str::slug($data[0]).'.example',
            'approved_at' => now()->subMonths(random_int(2, 14)),
        ]));

        Vendor::factory()->pending()->create([
            'user_id' => User::factory()->vendor()->create(['name' => 'Kupferkern Games', 'email' => 'bewerbung@kupferkern.test']),
            'name' => 'Kupferkern Games',
            'slug' => 'kupferkern-games',
            'tagline' => 'Indie-Studio für Survival- und Rollenspiel-Erweiterungen.',
        ]);

        return $vendors;
    }

    /**
     * @param  Collection<int, Vendor>  $vendors
     * @return Collection<int, Plugin>
     */
    private function seedPlugins(Collection $vendors, User $admin): Collection
    {
        $categories = Category::query()->pluck('id', 'slug');
        $platforms = Platform::query()->pluck('id', 'name');

        return collect(self::PLUGINS)->map(function (array $data) use ($vendors, $admin, $categories, $platforms): Plugin {
            [$name, $category, $vendorIndex, $price, $model, $summary, $tags, $platformNames, $features] = $data;
            $publishedAt = now()->subDays(random_int(20, 320));

            $plugin = Plugin::query()->create([
                'public_id' => $this->keys->pluginId(),
                'vendor_id' => $vendors[$vendorIndex]->id,
                'category_id' => $categories[$category],
                'name' => $name,
                'slug' => Str::slug($name),
                'summary' => $summary,
                'description' => $this->description($name, $summary, $features),
                'price_cents' => $price,
                'license_model' => LicenseModel::from($model),
                'status' => PluginStatus::Approved,
                'is_featured' => in_array($name, self::FEATURED, true),
                'published_at' => $publishedAt,
                'created_at' => $publishedAt,
            ]);

            $plugin->tags()->sync(collect($tags)->map(
                static fn (string $tag): int => Tag::query()->firstOrCreate(['slug' => Str::slug($tag)], ['name' => $tag])->id,
            ));

            $releases = random_int(2, 4);

            foreach (range(1, $releases) as $index) {
                $releasedAt = $publishedAt->copy()->addDays((int) (($index - 1) * $publishedAt->diffInDays(now()) / $releases));

                $this->createVersion(
                    $plugin,
                    '1.'.($index - 1).'.'.random_int(0, 6),
                    VersionStatus::Approved,
                    collect($platformNames)->map(static fn (string $platform): int => $platforms[$platform])->all(),
                    $releasedAt,
                    $admin,
                    $index === 1,
                );
            }

            $this->refreshRelease->handle($plugin);

            return $plugin;
        });
    }

    /**
     * @param  Collection<int, Vendor>  $vendors
     * @param  Collection<int, Plugin>  $plugins
     */
    private function seedModerationQueue(Collection $vendors, Collection $plugins): void
    {
        $categories = Category::query()->pluck('id', 'slug');
        $paper = Platform::query()->whereIn('slug', ['paper', 'purpur'])->pluck('id')->all();

        $pending = Plugin::query()->create([
            'public_id' => $this->keys->pluginId(),
            'vendor_id' => $vendors[4]->id,
            'category_id' => $categories['minigames-events'],
            'name' => 'QuestLine',
            'slug' => 'questline',
            'summary' => 'Story-Quests mit Dialogen, Verzweigungen und Belohnungen – komplett im Spiel editierbar.',
            'description' => $this->description('QuestLine', 'Story-Quests mit Dialogen, Verzweigungen und Belohnungen.', ['Dialog-Editor im Spiel', 'Verzweigte Questreihen', 'Belohnungen und Fortschrittsanzeige']),
            'price_cents' => 1299,
            'status' => PluginStatus::Pending,
        ]);
        $this->createVersion($pending, '1.0.0', VersionStatus::Pending, $paper, now()->subDay(), null, true);

        Plugin::query()->create([
            'public_id' => $this->keys->pluginId(),
            'vendor_id' => $vendors[0]->id,
            'category_id' => $categories['web-api'],
            'name' => 'MapSync',
            'slug' => 'mapsync',
            'summary' => 'Synchronisiert Live-Karten mehrerer Server in einer gemeinsamen Web-Ansicht.',
            'price_cents' => 899,
            'status' => PluginStatus::Draft,
        ]);

        $this->createVersion($plugins->firstWhere('name', 'VaultMarket'), '2.0.0', VersionStatus::Pending, $paper, now()->subHours(5), null, false);
    }

    /**
     * @param  Collection<int, Vendor>  $vendors
     */
    private function seedSales(Collection $vendors): void
    {
        Sale::query()->create([
            'name' => 'Pixelforge Herbst-Aktion',
            'percent' => 25,
            'scope' => SaleScope::Vendor,
            'vendor_id' => $vendors[1]->id,
            'vendor_share_percent' => 50,
            'ends_at' => now()->addWeeks(2),
        ]);

        Sale::query()->create([
            'name' => 'Black Week',
            'percent' => 15,
            'scope' => SaleScope::Shop,
            'vendor_share_percent' => 0,
            'is_active' => false,
        ]);
    }

    /**
     * @param  Collection<int, User>  $customers
     * @param  Collection<int, Plugin>  $plugins
     */
    private function seedOrders(Collection $customers, User $demoCustomer, Collection $plugins): void
    {
        $paid = $plugins->reject->isFree()->values();
        $owned = [];

        $place = function (User $buyer, Collection $items, Carbon $when, bool $fulfill) use (&$owned): void {
            $items = $items->reject(static fn (Plugin $plugin): bool => isset($owned[$buyer->id.':'.$plugin->id]));

            if ($items->isEmpty()) {
                return;
            }

            Date::setTestNow($when);

            $total = (int) $items->sum('price_cents');
            $method = collect(config('marketplace.settings.payment_methods'))->random();

            $order = $buyer->orders()->create([
                'number' => $this->keys->orderNumber(),
                'payment_method' => $method['id'],
                'payment_method_label' => $method['label'],
                'payment_fee_percent' => $method['percent'],
                'payment_fee_fixed_cents' => $method['fixed_cents'],
                'subtotal_cents' => $total,
                'total_cents' => $total,
                'billing' => $buyer->billingSnapshot(),
            ]);

            $order->items()->createMany($items->map(static fn (Plugin $plugin): array => [
                'plugin_id' => $plugin->id,
                'vendor_id' => $plugin->vendor_id,
                'plugin_name' => $plugin->name,
                'plugin_public_id' => $plugin->public_id,
                'license_model' => $plugin->license_model,
                'original_price_cents' => $plugin->price_cents,
                'unit_price_cents' => $plugin->price_cents,
            ])->all());

            if ($fulfill) {
                $this->fulfillOrder->handle($order);
                $items->each(static function (Plugin $plugin) use ($buyer, &$owned): void {
                    $owned[$buyer->id.':'.$plugin->id] = true;
                });
            }

            Date::setTestNow();
        };

        $place($demoCustomer, $paid->whereIn('name', ['Staff Manager', 'VaultMarket', 'SentinelAC']), now()->subDays(12), true);

        foreach (range(1, 70) as $ignored) {
            $place(
                $customers->random(),
                $paid->random(random_int(1, 3)),
                now()->subDays(random_int(0, 29))->subMinutes(random_int(0, 1400)),
                true,
            );
        }

        foreach (range(1, 4) as $ignored) {
            $place($customers->random(), $paid->random(random_int(1, 2)), now()->subMinutes(random_int(5, 600)), false);
        }

        Settlement::query()->where('created_at', '<', now()->subDays(21))->update([
            'status' => SettlementStatus::PaidOut,
            'released_at' => now()->subDays(14),
            'paid_out_at' => now()->subDays(7),
        ]);
        Settlement::query()->whereBetween('created_at', [now()->subDays(21), now()->subDays(7)])->update([
            'status' => SettlementStatus::Available,
            'released_at' => now()->subDays(3),
        ]);
    }

    private function seedReviews(): void
    {
        License::query()
            ->where('source', LicenseSource::Purchase)
            ->get()
            ->filter(static fn (): bool => random_int(1, 100) <= 60)
            ->each(static function (License $license): void {
                [$rating, $title, $body] = self::REVIEWS[array_rand(self::REVIEWS)];

                Review::query()->create([
                    'plugin_id' => $license->plugin_id,
                    'user_id' => $license->user_id,
                    'rating' => $rating,
                    'title' => $title,
                    'body' => $body,
                    'vendor_reply' => $rating <= 3 ? 'Danke für dein Feedback! Wir haben die genannten Punkte aufgenommen und arbeiten an Verbesserungen.' : null,
                    'vendor_replied_at' => $rating <= 3 ? now()->subDays(random_int(1, 5)) : null,
                    'created_at' => now()->subDays(random_int(0, 25)),
                ]);
            });

        Plugin::query()->has('reviews')->get()->each(fn (Plugin $plugin) => $this->refreshRating->handle($plugin));
    }

    private function seedDownloads(): void
    {
        $versions = PluginVersion::query()->approved()->get(['id', 'plugin_id'])->groupBy('plugin_id');

        $rows = License::query()->get(['id', 'user_id', 'plugin_id'])->flatMap(
            static fn (License $license): array => collect(range(1, random_int(1, 4)))->map(static fn (): array => [
                'user_id' => $license->user_id,
                'plugin_id' => $license->plugin_id,
                'plugin_version_id' => $versions[$license->plugin_id]->random()->id,
                'license_id' => $license->id,
                'ip' => fake()->ipv4(),
                'created_at' => now()->subDays(random_int(0, 29))->subMinutes(random_int(0, 1400)),
            ])->all(),
        );

        $rows->chunk(500)->each(static fn (Collection $chunk) => Download::query()->insert($chunk->all()));

        Plugin::query()->published()->get()->each(static function (Plugin $plugin): void {
            $plugin->update(['downloads_count' => $plugin->isFree() ? random_int(4_000, 28_000) : random_int(150, 9_500)]);
        });

        PluginVersion::query()->approved()->get()->each(
            static fn (PluginVersion $version) => $version->update(['downloads_count' => random_int(40, 3_200)]),
        );
    }

    /**
     * @param  Collection<int, Plugin>  $plugins
     */
    private function seedLicenseUsage(User $customer, Vendor $vendor, Collection $plugins): void
    {
        $this->issueLicense->handle($customer, $plugins->firstWhere('name', 'CoinFlow'), LicenseSource::Free);

        $license = $customer->licenses()->whereRelation('plugin', 'name', 'Staff Manager')->firstOrFail();

        foreach ([['INST-K7QP2-M4XZ9', '203.0.113.24', 'Lobby-01', 2], ['INST-B3HT8-W6RN5', '203.0.113.24', 'Survival-01', 50], ['INST-Z9DV4-C2LJ7', '198.51.100.7', 'Testserver', 60 * 30]] as [$instance, $ip, $name, $minutesAgo]) {
            $license->activations()->create([
                'instance_id' => $instance,
                'ip' => $ip,
                'name' => $name,
                'last_seen_at' => now()->subMinutes($minutesAgo),
            ]);
        }

        $this->subkeys->create($customer, 'Server von Jonas', $customer->licenses()->limit(2)->pluck('plugin_id')->all());
        $this->licenseCodes->generate($plugins->firstWhere('name', 'TicketDesk'), 5, 'Partner-Aktion');
        $this->licenseCodes->generate($vendor->plugins()->where('name', 'LinkBridge')->firstOrFail(), 3, 'Gewinnspiel');
    }

    /**
     * @param  Collection<int, Vendor>  $vendors
     */
    private function seedCommunity(User $admin, User $customer, Collection $vendors): void
    {
        foreach ([
            [AnnouncementType::News, AnnouncementAudience::All, 'Willkommen im neuen Marktplatz', 'Katalog, Lizenzverwaltung und Vendor-Portal wurden komplett überarbeitet. Viel Spaß beim Entdecken!'],
            [AnnouncementType::Update, AnnouncementAudience::Vendors, 'Update-API verfügbar', 'Plugins können neue Versionen jetzt über den Endpoint /api/v1/updates/check abfragen.'],
            [AnnouncementType::Sale, AnnouncementAudience::All, 'Herbst-Aktion', '25 % Rabatt auf alle Plugins von Pixelforge Studios – nur für kurze Zeit.'],
        ] as [$type, $audience, $title, $body]) {
            Announcement::query()->create([
                'type' => $type,
                'audience' => $audience,
                'title' => $title,
                'body' => $body,
                'created_by' => $admin->id,
            ]);
        }

        $platformTicket = $this->openTicket->handle($customer, [
            'subject' => 'Rechnungsadresse nachträglich ändern',
            'message' => 'Hallo, ich habe bei meiner letzten Bestellung eine falsche Anschrift angegeben. Kann die Rechnung korrigiert werden?',
        ]);
        $this->replyToTicket->handle($platformTicket, $admin, 'Hallo! Bitte aktualisiere die Anschrift in deinem Profil – wir stellen die Rechnung anschließend neu aus.');

        $this->openTicket->handle($customer, [
            'subject' => 'Staff Manager: Wochenbericht bleibt leer',
            'message' => 'Der Wochenbericht wird erstellt, enthält aber keine Aktivitäten. Version 1.x auf Docker.',
            'vendor_id' => $vendors->first()->id,
            'plugin_id' => $vendors->first()->plugins()->where('name', 'Staff Manager')->value('id'),
        ]);
    }

    /**
     * @param  list<int>  $platformIds
     */
    private function createVersion(
        Plugin $plugin,
        string $number,
        VersionStatus $status,
        array $platformIds,
        Carbon $releasedAt,
        ?User $reviewer,
        bool $initial,
    ): PluginVersion {
        $archive = $this->buildArchive($plugin, $number);
        $notes = $initial
            ? ['Erste Veröffentlichung']
            : collect(self::CHANGELOG_NOTES)->random(random_int(2, 4))->all();

        $version = $plugin->versions()->create([
            'public_id' => $this->keys->versionId(),
            'version' => $number,
            'changelog' => '- '.implode("\n- ", $notes),
            'game_versions' => ['1.20', '1.21'],
            'status' => $status,
            'file_path' => $archive['path'],
            'file_name' => Str::slug($plugin->name).'-'.$number.'.zip',
            'file_size' => $archive['size'],
            'file_hash' => $archive['hash'],
            'reviewed_by' => $status === VersionStatus::Approved ? $reviewer?->id : null,
            'reviewed_at' => $status === VersionStatus::Approved ? $releasedAt : null,
            'published_at' => $status === VersionStatus::Approved ? $releasedAt : null,
            'created_at' => $releasedAt,
        ]);

        $version->platforms()->sync($platformIds);

        return $version;
    }

    /**
     * @return array{path: string, size: int, hash: string}
     */
    private function buildArchive(Plugin $plugin, string $version): array
    {
        $temporary = tempnam(sys_get_temp_dir(), 'release');
        $zip = new ZipArchive();
        $zip->open($temporary, ZipArchive::OVERWRITE);
        $zip->addFromString('README.md', "# {$plugin->name} {$version}\n\n{$plugin->summary}\n");
        $zip->addFromString('plugin.yml', "name: {$plugin->name}\nversion: {$version}\nproduct-id: {$plugin->public_id}\n");
        $zip->addFromString('config.yml', "license-key: ''\nlanguage: de\n");
        $zip->close();

        $path = config('marketplace.releases.directory').'/'.$plugin->public_id.'/'.Str::uuid().'.zip';
        Storage::disk(config('marketplace.releases.disk'))->put($path, (string) file_get_contents($temporary));

        $archive = ['path' => $path, 'size' => (int) filesize($temporary), 'hash' => (string) hash_file('sha256', $temporary)];
        unlink($temporary);

        return $archive;
    }

    /**
     * @param  list<string>  $features
     */
    private function description(string $name, string $summary, array $features): string
    {
        $command = Str::lower(Str::before($name, ' '));
        $highlights = '- '.implode("\n- ", $features);

        return <<<MD
            ## Überblick

            {$summary}

            ## Highlights

            {$highlights}

            ## Installation

            1. Archiv herunterladen und in den `plugins`-Ordner entpacken.
            2. Server neu starten und den Lizenzschlüssel in der `config.yml` eintragen.
            3. Mit `/{$command} reload` die Konfiguration laden.

            ```yaml
            license-key: 'AALC-XXXX-XXXX-XXXX-XXXX'
            language: de
            ```

            ## Lizenz & Support

            Die Aktivierung erfolgt automatisch über die Lizenz-API. Bei Fragen hilft dir das Studio über ein Support-Ticket weiter.
            MD;
    }
}
