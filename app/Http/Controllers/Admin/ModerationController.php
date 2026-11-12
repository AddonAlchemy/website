<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Plugins\ReviewPlugin;
use App\Actions\Plugins\ReviewVersion;
use App\Enums\PluginStatus;
use App\Enums\VersionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectionRequest;
use App\Models\Plugin;
use App\Models\PluginVersion;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ModerationController extends Controller
{
    public function index(): View
    {
        return view('admin.moderation', [
            'plugins' => Plugin::query()
                ->where('status', PluginStatus::Pending)
                ->with(['vendor:id,name,slug', 'category:id,name,slug,icon', 'versions.platforms'])
                ->oldest('updated_at')
                ->get(),
            'versions' => PluginVersion::query()
                ->where('status', VersionStatus::Pending)
                ->whereHas('plugin', static fn ($query) => $query->where('status', PluginStatus::Approved))
                ->with(['plugin:id,name,slug,vendor_id,latest_version', 'plugin.vendor:id,name', 'platforms'])
                ->oldest()
                ->get(),
        ]);
    }

    public function approvePlugin(Request $request, Plugin $plugin, ReviewPlugin $review): RedirectResponse
    {
        $review->approve($plugin, $request->user());

        return back()->with('success', $plugin->name.' wurde veröffentlicht.');
    }

    public function rejectPlugin(RejectionRequest $request, Plugin $plugin, ReviewPlugin $review): RedirectResponse
    {
        $review->reject($plugin, $request->validated('reason'));

        return back()->with('success', $plugin->name.' wurde abgelehnt.');
    }

    public function approveVersion(Request $request, PluginVersion $version, ReviewVersion $review): RedirectResponse
    {
        $review->approve($version, $request->user());

        return back()->with('success', 'Version '.$version->version.' wurde freigegeben.');
    }

    public function rejectVersion(RejectionRequest $request, PluginVersion $version, ReviewVersion $review): RedirectResponse
    {
        $review->reject($version, $request->user(), $request->validated('reason'));

        return back()->with('success', 'Version '.$version->version.' wurde abgelehnt.');
    }
}
