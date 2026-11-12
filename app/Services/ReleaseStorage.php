<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Plugin;
use App\Models\PluginVersion;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ReleaseStorage
{
    public function store(UploadedFile $file, Plugin $plugin): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $directory = config('marketplace.releases.directory').'/'.$plugin->public_id;

        return $this->disk()->putFileAs($directory, $file, Str::uuid()->toString().'.'.$extension);
    }

    public function delete(string $path): void
    {
        $this->disk()->delete($path);
    }

    public function exists(PluginVersion $version): bool
    {
        return $this->disk()->exists($version->file_path);
    }

    public function download(PluginVersion $version): StreamedResponse
    {
        return $this->disk()->download($version->file_path, $version->file_name, [
            'Content-Type' => 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public static function sanitizeFileName(string $name): string
    {
        $clean = preg_replace('/[^\w.\- ]+/u', '', $name) ?? '';

        return Str::limit($clean !== '' ? $clean : 'release.zip', 150, '');
    }

    private function disk(): Filesystem
    {
        return Storage::disk(config('marketplace.releases.disk'));
    }
}
