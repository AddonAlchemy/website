<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\InvalidReleaseArchive;
use App\Services\ReleaseArchiveInspector;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

final class ReleaseArchiveInspectorTest extends TestCase
{
    public function test_it_reports_checksum_and_entry_statistics(): void
    {
        $upload = $this->zipUpload(['plugin.yml' => 'name: Demo', 'src/Main.java' => 'class Main {}']);

        $archive = (new ReleaseArchiveInspector())->inspect($upload);

        $this->assertSame(2, $archive->entries);
        $this->assertSame(hash_file('sha256', $upload->getRealPath()), $archive->sha256);
        $this->assertSame($upload->getSize(), $archive->sizeBytes);
    }

    public function test_it_rejects_path_traversal_entries(): void
    {
        $this->expectException(InvalidReleaseArchive::class);

        (new ReleaseArchiveInspector())->inspect($this->zipUpload(['docs/../../evil.sh' => 'rm -rf /']));
    }

    public function test_it_rejects_absolute_paths(): void
    {
        $this->expectException(InvalidReleaseArchive::class);

        (new ReleaseArchiveInspector())->inspect($this->zipUpload(['/etc/cron.d/job' => '* * * * *']));
    }

    public function test_it_rejects_archives_with_suspicious_compression_ratio(): void
    {
        $this->expectException(InvalidReleaseArchive::class);

        (new ReleaseArchiveInspector())->inspect($this->zipUpload(['zeros.bin' => str_repeat("\0", 8_000_000)]));
    }

    public function test_it_rejects_empty_and_non_zip_files(): void
    {
        $inspector = new ReleaseArchiveInspector();

        $uploads = [
            UploadedFile::fake()->createWithContent('empty.zip', "PK\x05\x06".str_repeat("\0", 18)),
            UploadedFile::fake()->createWithContent('fake.zip', 'plain text'),
        ];

        foreach ($uploads as $upload) {
            try {
                $inspector->inspect($upload);
                $this->fail('Archive should have been rejected.');
            } catch (InvalidReleaseArchive $exception) {
                $this->assertNotSame('', $exception->getMessage());
            }
        }
    }
}
