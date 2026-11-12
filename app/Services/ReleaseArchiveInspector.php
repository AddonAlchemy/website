<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\InspectedArchive;
use App\Exceptions\InvalidReleaseArchive;
use Illuminate\Http\UploadedFile;
use ZipArchive;

final class ReleaseArchiveInspector
{
    private const UNIX_SYMLINK_MODE = 0o120000;

    private const UNIX_FILE_TYPE_MASK = 0o170000;

    /**
     * @throws InvalidReleaseArchive
     */
    public function inspect(UploadedFile $file): InspectedArchive
    {
        $path = $file->getRealPath();
        $limits = config('marketplace.releases');

        if ($path === false || ! $file->isValid()) {
            throw new InvalidReleaseArchive('Die Datei konnte nicht gelesen werden.');
        }

        $archive = new ZipArchive();

        if ($archive->open($path, ZipArchive::RDONLY) !== true) {
            throw new InvalidReleaseArchive('Die Datei ist kein gültiges ZIP-Archiv.');
        }

        try {
            $entries = $archive->numFiles;

            if ($entries === 0) {
                throw new InvalidReleaseArchive('Das Archiv ist leer.');
            }

            if ($entries > $limits['max_entries']) {
                throw new InvalidReleaseArchive('Das Archiv enthält zu viele Dateien.');
            }

            $uncompressed = 0;
            $compressed = 0;

            for ($index = 0; $index < $entries; $index++) {
                $stat = $archive->statIndex($index);

                if ($stat === false) {
                    throw new InvalidReleaseArchive('Das Archiv ist beschädigt.');
                }

                $this->assertSafeEntry($archive, $index, $stat);

                $uncompressed += $stat['size'];
                $compressed += $stat['comp_size'];
            }

            if ($uncompressed > $limits['max_uncompressed_bytes']) {
                throw new InvalidReleaseArchive('Der entpackte Inhalt des Archivs ist zu groß.');
            }

            if ($compressed > 0 && $uncompressed / $compressed > $limits['max_compression_ratio']) {
                throw new InvalidReleaseArchive('Das Archiv weist eine auffällige Kompressionsrate auf.');
            }
        } finally {
            $archive->close();
        }

        return new InspectedArchive(
            sha256: hash_file('sha256', $path),
            sizeBytes: (int) $file->getSize(),
            entries: $entries,
            uncompressedBytes: $uncompressed,
        );
    }

    /**
     * @param  array<string, mixed>  $stat
     *
     * @throws InvalidReleaseArchive
     */
    private function assertSafeEntry(ZipArchive $archive, int $index, array $stat): void
    {
        $name = str_replace('\\', '/', (string) $stat['name']);

        if ($name === '' || str_contains($name, "\0")) {
            throw new InvalidReleaseArchive('Das Archiv enthält ungültige Dateinamen.');
        }

        if (str_starts_with($name, '/') || preg_match('/^[A-Za-z]:/', $name) === 1 || in_array('..', explode('/', $name), true)) {
            throw new InvalidReleaseArchive('Das Archiv enthält unzulässige Pfadangaben.');
        }

        if (($stat['encryption_method'] ?? 0) !== 0) {
            throw new InvalidReleaseArchive('Passwortgeschützte Archive werden nicht akzeptiert.');
        }

        if ($archive->getExternalAttributesIndex($index, $system, $attributes)
            && $system === ZipArchive::OPSYS_UNIX
            && (($attributes >> 16) & self::UNIX_FILE_TYPE_MASK) === self::UNIX_SYMLINK_MODE) {
            throw new InvalidReleaseArchive('Das Archiv enthält symbolische Links.');
        }
    }
}
