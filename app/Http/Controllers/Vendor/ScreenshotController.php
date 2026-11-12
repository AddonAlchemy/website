<?php

declare(strict_types=1);

namespace App\Http\Controllers\Vendor;

use App\Actions\Plugins\ManageScreenshots;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\StoreScreenshotRequest;
use App\Models\Plugin;
use App\Models\PluginScreenshot;
use Illuminate\Http\RedirectResponse;

final class ScreenshotController extends Controller
{
    public function __construct(private readonly ManageScreenshots $screenshots)
    {
    }

    public function store(StoreScreenshotRequest $request, Plugin $plugin): RedirectResponse
    {
        $this->authorize('update', $plugin);

        $this->screenshots->add($plugin, $request->file('images'), $request->validated('caption'));

        return redirect(route('vendor.plugins.edit', $plugin).'#gallery')->with('success', 'Screenshots hinzugefügt.');
    }

    public function destroy(PluginScreenshot $screenshot): RedirectResponse
    {
        $this->authorize('update', $screenshot->plugin);

        $this->screenshots->remove($screenshot);

        return redirect(route('vendor.plugins.edit', $screenshot->plugin).'#gallery')->with('success', 'Screenshot entfernt.');
    }
}
