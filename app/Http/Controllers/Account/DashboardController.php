<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return view('account.dashboard', [
            'user' => $user->loadCount(['licenses', 'orders', 'favorites', 'downloads']),
            'licenses' => $user->licenses()
                ->with(['plugin:id,name,slug,public_id,icon_path,latest_version', 'plugin.latestRelease'])
                ->latest()
                ->limit(5)
                ->get(),
            'orders' => $user->orders()->withCount('items')->latest()->limit(5)->get(),
            'vendor' => $user->vendor,
        ]);
    }
}
