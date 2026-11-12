<?php

declare(strict_types=1);

namespace App\Http\Controllers\Vendor;

use App\Actions\Plugins\DeleteVersion;
use App\Actions\Plugins\StoreVersion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\StoreVersionRequest;
use App\Models\Plugin;
use App\Models\PluginVersion;
use Illuminate\Http\RedirectResponse;

final class VersionController extends Controller
{
    public function store(StoreVersionRequest $request, Plugin $plugin, StoreVersion $storeVersion): RedirectResponse
    {
        $this->authorize('update', $plugin);

        $storeVersion->handle($plugin, $request->payload(), $request->file('archive'));

        return redirect(route('vendor.plugins.edit', $plugin).'#versions')
            ->with('success', 'Version hochgeladen – sie wird nach der Prüfung veröffentlicht.');
    }

    public function destroy(PluginVersion $version, DeleteVersion $deleteVersion): RedirectResponse
    {
        $this->authorize('delete', $version);

        $deleteVersion->handle($version);

        return redirect(route('vendor.plugins.edit', $version->plugin).'#versions')->with('success', 'Version entfernt.');
    }
}
