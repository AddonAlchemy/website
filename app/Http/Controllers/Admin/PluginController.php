<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Plugins\RemovePlugin;
use App\Enums\PluginStatus;
use App\Http\Controllers\Controller;
use App\Models\Plugin;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class PluginController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $status = PluginStatus::tryFrom((string) $request->query('status'));

        return view('admin.plugins', [
            'plugins' => Plugin::query()
                ->with(['vendor:id,name,slug', 'category:id,name,slug,icon'])
                ->when($search !== '', static fn ($query) => $query->whereAny(['name', 'public_id'], 'like', '%'.$search.'%'))
                ->when($status, static fn ($query) => $query->where('status', $status))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'statuses' => PluginStatus::cases(),
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function feature(Plugin $plugin): RedirectResponse
    {
        $plugin->update(['is_featured' => ! $plugin->is_featured]);

        return back()->with('success', $plugin->is_featured ? 'Plugin wird jetzt hervorgehoben.' : 'Hervorhebung entfernt.');
    }

    public function destroy(Plugin $plugin, RemovePlugin $remove): RedirectResponse
    {
        $deleted = $remove->handle($plugin);

        return back()->with('success', $deleted ? 'Plugin gelöscht.' : 'Plugin archiviert – bestehende Lizenzen bleiben gültig.');
    }
}
