<?php

declare(strict_types=1);

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Services\VendorStatistics;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class DashboardController extends Controller
{
    public function __invoke(Request $request, VendorStatistics $statistics): View
    {
        $vendor = $request->user()->vendor;

        return view('vendor.dashboard', [
            'vendor' => $vendor,
            'stats' => $statistics->for($vendor),
            'recentSettlements' => $vendor->settlements()->with('order:id,number')->latest()->limit(5)->get(),
        ]);
    }
}
