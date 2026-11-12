<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MarketplaceStatistics;
use Illuminate\Contracts\View\View;

final class DashboardController extends Controller
{
    public function __invoke(MarketplaceStatistics $statistics): View
    {
        return view('admin.dashboard', ['stats' => $statistics->overview()]);
    }
}
