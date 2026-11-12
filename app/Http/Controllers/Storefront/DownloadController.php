<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Actions\Downloads\RecordDownload;
use App\Actions\Licensing\IssueLicense;
use App\Enums\LicenseSource;
use App\Http\Controllers\Controller;
use App\Models\PluginVersion;
use App\Services\DownloadLinkFactory;
use App\Services\ReleaseStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DownloadController extends Controller
{
    public function start(
        Request $request,
        PluginVersion $version,
        IssueLicense $issueLicense,
        DownloadLinkFactory $links,
    ): RedirectResponse {
        $this->authorize('download', $version);

        $user = $request->user();
        $plugin = $version->plugin;
        $license = $user->licenses()->usable()->where('plugin_id', $plugin->id)->first();

        if ($license === null && $plugin->isFree() && $plugin->isPublished()) {
            $license = $issueLicense->handle($user, $plugin, LicenseSource::Free);
        }

        return redirect()->away($links->make($version, $user, $license));
    }

    public function file(
        Request $request,
        PluginVersion $version,
        ReleaseStorage $storage,
        RecordDownload $recordDownload,
    ): StreamedResponse {
        abort_unless($storage->exists($version), 404, 'Datei nicht vorhanden.');

        $recordDownload->handle(
            $version,
            $request->integer('u') ?: null,
            $request->integer('l') ?: null,
            $request->ip(),
        );

        return $storage->download($version);
    }
}
