<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LicenseActivation;
use App\Models\Ticket;
use Illuminate\Contracts\View\View;

final class MonitoringController extends Controller
{
    public function activations(): View
    {
        return view('admin.activations', [
            'activations' => LicenseActivation::query()
                ->with(['license:id,user_id,plugin_id,key', 'license.user:id,email', 'license.plugin:id,name,public_id'])
                ->latest('last_seen_at')
                ->paginate(30),
            'activeToday' => LicenseActivation::query()->where('last_seen_at', '>=', now()->subDay())->count(),
            'distinctIps' => LicenseActivation::query()->distinct()->count('ip'),
        ]);
    }

    public function tickets(): View
    {
        return view('admin.tickets', [
            'tickets' => Ticket::query()
                ->with(['user:id,name,email', 'vendor:id,name'])
                ->latest('last_reply_at')
                ->paginate(25),
        ]);
    }
}
