<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Auth\ProvisionAdmin;
use App\Enums\UserRole;
use App\Models\Plugin;
use App\Models\User;
use App\Support\EnvironmentFile;
use Database\Seeders\CategorySeeder;
use Database\Seeders\DemoMarketplaceSeeder;
use Database\Seeders\PlatformSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;

final class InstallMarketplace extends Command
{
    private const REQUIRED_EXTENSIONS = ['ctype', 'dom', 'fileinfo', 'json', 'mbstring', 'openssl', 'pdo', 'tokenizer', 'xml', 'zip'];

    private const DATABASE_DRIVERS = [
        'sqlite' => 'SQLite (keine Einrichtung nötig)',
        'mysql' => 'MySQL / MariaDB',
        'pgsql' => 'PostgreSQL',
    ];

    private const DEFAULT_PORTS = ['mysql' => '3306', 'pgsql' => '5432'];

    private const CONNECTION_ATTEMPTS = 3;

    protected $signature = 'marketplace:install
        {--name= : Name der Anwendung}
        {--url= : Öffentliche URL der Anwendung}
        {--production : Für den Produktivbetrieb konfigurieren}
        {--db= : Datenbank-Treiber (sqlite, mysql, pgsql)}
        {--db-host= : Datenbank-Host}
        {--db-port= : Datenbank-Port}
        {--db-name= : Name der Datenbank}
        {--db-user= : Datenbank-Benutzer}
        {--db-password= : Datenbank-Passwort}
        {--demo : Demo-Daten einspielen}
        {--admin-email= : E-Mail-Adresse des ersten Administrators}
        {--admin-password= : Passwort des ersten Administrators}
        {--legacy= : Pfad zur Alt-Installation für den Datenimport}
        {--skip-assets : Frontend-Build überspringen}
        {--serve : Entwicklungsserver nach der Installation starten}';

    protected $description = 'Richtet den Marktplatz vollständig ein: Umgebung, Datenbank, Stammdaten, Administrator und Assets';

    private EnvironmentFile $environment;

    private bool $production = false;

    private bool $demoSeeded = false;

    public function handle(ProvisionAdmin $provisionAdmin): int
    {
        $this->components->info('Addon Alchemy Marketplace – Installation');

        try {
            $this->assertRequirements();

            $this->environment = EnvironmentFile::at($this->laravel->environmentFilePath(), base_path('.env.example'));

            $this->configureApplication();
            $this->configureDatabase();
            $this->ensureApplicationKey();
            $this->migrateAndSeed();

            $admin = $this->provisionAdministrator($provisionAdmin);

            $this->importLegacyData();
            $this->linkStorage();
            $this->buildAssets();
            $this->optimize();
        } catch (RuntimeException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->printSummary($admin);

        return $this->shouldServe() ? $this->call('serve') : self::SUCCESS;
    }

    /**
     * @throws RuntimeException
     */
    private function assertRequirements(): void
    {
        $missing = array_filter(self::REQUIRED_EXTENSIONS, static fn (string $extension): bool => ! extension_loaded($extension));
        $locked = array_filter(
            [storage_path(), storage_path('framework'), storage_path('logs'), base_path('bootstrap/cache')],
            static fn (string $directory): bool => ! is_writable($directory),
        );

        $this->components->twoColumnDetail('PHP '.PHP_VERSION, $this->status(version_compare(PHP_VERSION, '8.3.0', '>=')));
        $this->components->twoColumnDetail('PHP-Erweiterungen', $this->status($missing === []));
        $this->components->twoColumnDetail('Schreibrechte', $this->status($locked === []));

        if (version_compare(PHP_VERSION, '8.3.0', '<')) {
            throw new RuntimeException('Es wird mindestens PHP 8.3 benötigt.');
        }

        if ($missing !== []) {
            throw new RuntimeException('Fehlende PHP-Erweiterungen: '.implode(', ', $missing));
        }

        if ($locked !== []) {
            throw new RuntimeException('Nicht beschreibbar: '.implode(', ', $locked));
        }
    }

    private function configureApplication(): void
    {
        $this->production = $this->option('production')
            || ($this->input->isInteractive() && select('Für welche Umgebung wird installiert?', [
                'local' => 'Lokale Entwicklung / Test',
                'production' => 'Produktivbetrieb',
            ], 'local') === 'production');

        $name = $this->option('name') ?? $this->prompt('Name des Marktplatzes', (string) config('app.name'));
        $url = rtrim($this->option('url') ?? $this->prompt('Öffentliche URL', $this->defaultUrl()), '/');

        $this->environment->set([
            'APP_NAME' => $name,
            'APP_ENV' => $this->production ? 'production' : 'local',
            'APP_DEBUG' => ! $this->production,
            'APP_URL' => $url,
            'LOG_LEVEL' => $this->production ? 'warning' : 'debug',
            'MARKETPLACE_AUTO_CAPTURE' => ! $this->production,
        ]);

        config(['app.name' => $name, 'app.url' => $url]);
        URL::forceRootUrl($url);
    }

    /**
     * @throws RuntimeException
     */
    private function configureDatabase(): void
    {
        if ($this->option('db') === null && ! $this->input->isInteractive()) {
            $this->assertDatabaseReachable();

            return;
        }

        for ($attempt = 1; $attempt <= self::CONNECTION_ATTEMPTS; $attempt++) {
            $driver = $this->option('db') ?? select('Welche Datenbank soll verwendet werden?', self::DATABASE_DRIVERS, (string) config('database.default'));

            if (! array_key_exists($driver, self::DATABASE_DRIVERS)) {
                throw new RuntimeException('Unbekannter Datenbank-Treiber: '.$driver);
            }

            if (! extension_loaded('pdo_'.$driver)) {
                throw new RuntimeException('Die PHP-Erweiterung pdo_'.$driver.' ist nicht installiert.');
            }

            $driver === 'sqlite' ? $this->useSqlite() : $this->useServerDatabase($driver);

            try {
                $this->assertDatabaseReachable();

                return;
            } catch (RuntimeException $exception) {
                if (! $this->input->isInteractive() || $attempt === self::CONNECTION_ATTEMPTS) {
                    throw $exception;
                }

                $this->components->warn($exception->getMessage().' Bitte Zugangsdaten prüfen.');
            }
        }
    }

    private function useSqlite(): void
    {
        $path = database_path('database.sqlite');

        if (! is_file($path)) {
            touch($path);
        }

        $this->environment->set(['DB_CONNECTION' => 'sqlite']);
        $this->environment->comment(['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD']);

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => $path]);
        DB::purge('sqlite');
    }

    private function useServerDatabase(string $driver): void
    {
        $settings = [
            'host' => $this->option('db-host') ?? $this->prompt('Datenbank-Host', '127.0.0.1'),
            'port' => $this->option('db-port') ?? $this->prompt('Datenbank-Port', self::DEFAULT_PORTS[$driver]),
            'database' => $this->option('db-name') ?? $this->prompt('Name der Datenbank', 'marketplace'),
            'username' => $this->option('db-user') ?? $this->prompt('Datenbank-Benutzer', 'root'),
            'password' => $this->option('db-password') ?? ($this->input->isInteractive() ? password('Datenbank-Passwort') : ''),
        ];

        $this->environment->set([
            'DB_CONNECTION' => $driver,
            'DB_HOST' => $settings['host'],
            'DB_PORT' => $settings['port'],
            'DB_DATABASE' => $settings['database'],
            'DB_USERNAME' => $settings['username'],
            'DB_PASSWORD' => $settings['password'],
        ]);

        config([
            'database.default' => $driver,
            'database.connections.'.$driver => array_merge((array) config('database.connections.'.$driver), $settings),
        ]);
        DB::purge($driver);
    }

    /**
     * @throws RuntimeException
     */
    private function assertDatabaseReachable(): void
    {
        try {
            DB::connection()->getPdo();
        } catch (Throwable $exception) {
            throw new RuntimeException('Keine Verbindung zur Datenbank: '.$exception->getMessage());
        }

        $this->components->twoColumnDetail('Datenbank ('.config('database.default').')', $this->status(true));
    }

    private function ensureApplicationKey(): void
    {
        if (blank(config('app.key'))) {
            $this->step('Anwendungsschlüssel erzeugen', fn (): int => $this->callSilent('key:generate', ['--force' => true]));
        }
    }

    /**
     * @throws RuntimeException
     */
    private function migrateAndSeed(): void
    {
        $this->step('Datenbank-Schema migrieren', fn (): int => $this->artisan('migrate', '--force'));
        $this->step('Plattformen und Kategorien anlegen', function (): int {
            return $this->artisan('db:seed', '--class='.PlatformSeeder::class, '--force')
                + $this->artisan('db:seed', '--class='.CategorySeeder::class, '--force');
        });

        if (! $this->wantsDemoData()) {
            return;
        }

        if (! class_exists(\Faker\Factory::class)) {
            throw new RuntimeException('Demo-Daten benötigen die Entwicklungs-Abhängigkeiten (composer install ohne --no-dev).');
        }

        $this->step('Demo-Daten einspielen', fn (): int => $this->artisan('db:seed', '--class='.DemoMarketplaceSeeder::class, '--force'));

        $this->demoSeeded = true;
    }

    private function wantsDemoData(): bool
    {
        if (Plugin::query()->exists()) {
            return false;
        }

        return $this->option('demo')
            || ($this->input->isInteractive() && confirm('Demo-Daten (Studios, Plugins, Bestellungen) einspielen?', ! $this->production));
    }

    /**
     * @throws RuntimeException
     */
    private function provisionAdministrator(ProvisionAdmin $provisionAdmin): ?User
    {
        $email = $this->option('admin-email');
        $existing = User::query()->where('role', UserRole::Admin)->oldest('id')->first();

        if ($email === null && ($existing !== null || ! $this->input->isInteractive())) {
            return $existing;
        }

        $email ??= text('E-Mail-Adresse des Administrators', required: true, validate: static fn (string $value): ?string => filter_var($value, FILTER_VALIDATE_EMAIL) ? null : 'Bitte eine gültige E-Mail-Adresse angeben.');
        $secret = $this->option('admin-password');

        if ($secret === null && $this->input->isInteractive() && ! User::query()->where('email', $email)->exists()) {
            $secret = password('Passwort des Administrators', required: true, validate: static fn (string $value): ?string => mb_strlen($value) >= 8 ? null : 'Mindestens 8 Zeichen.');
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Die Administrator-E-Mail ist ungültig.');
        }

        if ($secret !== null && mb_strlen($secret) < 8) {
            throw new RuntimeException('Das Administrator-Passwort muss mindestens 8 Zeichen lang sein.');
        }

        if ($secret === null && ! User::query()->where('email', $email)->exists()) {
            throw new RuntimeException('Für ein neues Administrator-Konto wird --admin-password benötigt.');
        }

        $admin = $provisionAdmin->handle($email, $secret);
        $this->components->twoColumnDetail('Administrator '.$admin->email, $this->status(true));

        return $admin;
    }

    /**
     * @throws RuntimeException
     */
    private function importLegacyData(): void
    {
        $path = $this->option('legacy');

        if ($path === null && $this->input->isInteractive() && ! $this->demoSeeded) {
            $path = text('Pfad zu einer Alt-Installation für den Datenimport (leer lassen zum Überspringen)') ?: null;
        }

        if ($path !== null) {
            $this->step('Alt-Daten importieren', fn (): int => $this->artisan('marketplace:import-legacy', $path));
        }
    }

    private function linkStorage(): void
    {
        if (! file_exists(public_path('storage'))) {
            $this->step('Storage-Verknüpfung anlegen', fn (): int => $this->artisan('storage:link'));
        }
    }

    /**
     * @throws RuntimeException
     */
    private function buildAssets(): void
    {
        if ($this->option('skip-assets')) {
            return;
        }

        $npm = (new ExecutableFinder())->find('npm');

        if ($npm === null) {
            $this->components->warn('npm wurde nicht gefunden – bitte Node.js installieren und anschließend "npm install && npm run build" ausführen.');

            return;
        }

        if (! is_dir(base_path('node_modules'))) {
            $install = is_file(base_path('package-lock.json')) ? 'ci' : 'install';
            $this->step('Frontend-Abhängigkeiten installieren', fn (): int => $this->runProcess([$npm, $install, '--no-audit', '--no-fund']));
        }

        $this->step('Assets bauen', fn (): int => $this->runProcess([$npm, 'run', 'build']));
    }

    private function optimize(): void
    {
        if ($this->production) {
            $this->step('Konfiguration, Routen und Views cachen', fn (): int => $this->artisan('optimize'));
        }
    }

    private function printSummary(?User $admin): void
    {
        $this->newLine();
        $this->components->info('Installation abgeschlossen.');
        $this->components->twoColumnDetail('Adresse', (string) config('app.url'));
        $this->components->twoColumnDetail('Administrator', $admin !== null ? $admin->email : 'noch nicht angelegt (php artisan marketplace:admin <email>)');

        if (User::query()->where('email', 'admin@addonalchemy.test')->exists()) {
            $this->components->twoColumnDetail('Demo-Zugänge', 'admin@, vendor@, customer@addonalchemy.test · Passwort "password"');
        }

        $this->newLine();
        $this->components->bulletList($this->production
            ? [
                'Webserver auf das Verzeichnis public/ zeigen lassen',
                'Cron einrichten: * * * * * php '.base_path('artisan').' schedule:run',
                'Queue-Worker starten: php artisan queue:work',
            ]
            : ['Entwicklungsserver starten: php artisan serve']);
    }

    private function shouldServe(): bool
    {
        if ($this->production) {
            return false;
        }

        return $this->option('serve')
            || ($this->input->isInteractive() && confirm('Entwicklungsserver jetzt starten?', false));
    }

    /**
     * @param  callable(): int  $task
     *
     * @throws RuntimeException
     */
    private function step(string $label, callable $task): void
    {
        $succeeded = false;
        $failure = null;

        $this->components->task($label, function () use ($task, &$succeeded, &$failure): bool {
            try {
                return $succeeded = $task() === self::SUCCESS;
            } catch (Throwable $exception) {
                $failure = $exception->getMessage().($this->output->isVerbose()
                    ? ' ('.$exception->getFile().':'.$exception->getLine().')'."\n".$exception->getTraceAsString()
                    : '');

                return false;
            }
        });

        if (! $succeeded) {
            throw new RuntimeException($label.' fehlgeschlagen'.($failure !== null ? ': '.$failure : '.'));
        }
    }

    /**
     * @param  list<string>  $command
     * @param  array<string, string|false>  $environment
     *
     * @throws RuntimeException
     */
    private function runProcess(array $command, array $environment = []): int
    {
        $process = new Process($command, base_path(), $environment, timeout: 900);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(trim($process->getErrorOutput() ?: $process->getOutput()));
        }

        return self::SUCCESS;
    }

    /**
     * @throws RuntimeException
     */
    private function artisan(string ...$arguments): int
    {
        return $this->runProcess(
            [PHP_BINARY, base_path('artisan'), ...$arguments, '--no-interaction'],
            array_fill_keys($this->environment->keys(), false),
        );
    }

    private function prompt(string $label, string $default): string
    {
        return $this->input->isInteractive() ? text($label, default: $default, required: true) : $default;
    }

    private function defaultUrl(): string
    {
        $current = (string) config('app.url');

        return $current === 'http://localhost' ? 'http://localhost:8000' : $current;
    }

    private function status(bool $passed): string
    {
        return $passed ? '<fg=green;options=bold>OK</>' : '<fg=red;options=bold>FEHLT</>';
    }
}
