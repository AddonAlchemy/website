<?php

declare(strict_types=1);

namespace App\Data;

final readonly class InspectedArchive
{
    public function __construct(
        public string $sha256,
        public int $sizeBytes,
        public int $entries,
        public int $uncompressedBytes,
    ) {
    }
}
