<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Legacy\LegacyImporter;
use Illuminate\Console\Command;
use RuntimeException;

final class ImportLegacyData extends Command
{
    protected $signature = 'marketplace:import-legacy
        {path : Verzeichnis der alten Installation (enthält data/db.json)}
        {--category=administration : Slug der Kategorie für importierte Produkte}';

    protected $description = 'Übernimmt Benutzer, Produkte, Lizenzen und Bestellungen aus der JSON-Datenbank der Alt-Installation';

    public function handle(LegacyImporter $importer): int
    {
        try {
            $report = $importer->import((string) $this->argument('path'), (string) $this->option('category'));
        } catch (RuntimeException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->table(['Bereich', 'Importiert'], collect($report)->map(
            static fn (int $count, string $area): array => [$area, $count],
        )->values()->all());

        $this->components->info('Import abgeschlossen.');

        return self::SUCCESS;
    }
}
