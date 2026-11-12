<?php

declare(strict_types=1);

namespace App\Http\Controllers\Vendor;

use App\Actions\Plugins\RemovePlugin;
use App\Actions\Plugins\SavePlugin;
use App\Actions\Plugins\SubmitPlugin;
use App\Enums\LicenseModel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\SavePluginRequest;
use App\Models\Category;
use App\Models\Platform;
use App\Models\Plugin;
use App\Services\Settings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class PluginController extends Controller
{
    public function __construct(private readonly SavePlugin $savePlugin)
    {
    }

    public function index(Request $request): View
    {
        return view('vendor.plugins.index', [
            'plugins' => $request->user()->vendor->plugins()
                ->with('category:id,name,slug,icon')
                ->withCount('versions')
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(Settings $settings): View
    {
        $this->authorize('create', Plugin::class);

        return view('vendor.plugins.create', $this->formData($settings));
    }

    public function store(SavePluginRequest $request): RedirectResponse
    {
        $this->authorize('create', Plugin::class);

        $plugin = $this->savePlugin->create($request->user()->vendor, $request->payload(), $request->file('icon'));

        return to_route('vendor.plugins.edit', $plugin)
            ->with('success', 'Plugin angelegt. Lade jetzt eine Version hoch und reiche es zur Prüfung ein.');
    }

    public function edit(Plugin $plugin, Settings $settings): View
    {
        $this->authorize('update', $plugin);

        return view('vendor.plugins.edit', [
            ...$this->formData($settings),
            'plugin' => $plugin->load(['tags', 'screenshots', 'versions.platforms']),
            'platforms' => Platform::query()->orderBy('position')->get(),
            'codes' => $plugin->licenseCodes()->with('redeemer:id,email')->latest()->limit(100)->get(),
        ]);
    }

    public function update(SavePluginRequest $request, Plugin $plugin): RedirectResponse
    {
        $this->authorize('update', $plugin);

        $this->savePlugin->update($plugin, $request->payload(), $request->file('icon'));

        return to_route('vendor.plugins.edit', $plugin)->with('success', 'Plugin gespeichert.');
    }

    public function submit(Plugin $plugin, SubmitPlugin $submit): RedirectResponse
    {
        $this->authorize('update', $plugin);

        $submit->handle($plugin);

        return back()->with('success', 'Plugin zur Prüfung eingereicht.');
    }

    public function destroy(Plugin $plugin, RemovePlugin $remove): RedirectResponse
    {
        $this->authorize('delete', $plugin);

        $deleted = $remove->handle($plugin);

        return to_route('vendor.plugins.index')->with(
            'success',
            $deleted ? 'Plugin gelöscht.' : 'Plugin archiviert – bestehende Lizenzen bleiben gültig.',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Settings $settings): array
    {
        return [
            'categories' => Category::query()->orderBy('position')->get(['id', 'name']),
            'licenseModels' => LicenseModel::cases(),
            'minPriceCents' => $settings->minPriceCents(),
        ];
    }
}
