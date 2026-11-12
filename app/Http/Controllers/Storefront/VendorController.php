<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Contracts\View\View;

final class VendorController extends Controller
{
    public function index(): View
    {
        return view('storefront.vendors.index', [
            'vendors' => Vendor::query()
                ->approved()
                ->withCount(['plugins' => static fn ($query) => $query->published()])
                ->withSum(['plugins' => static fn ($query) => $query->published()], 'downloads_count')
                ->orderByDesc('plugins_count')
                ->paginate(18),
        ]);
    }

    public function show(Vendor $vendor): View
    {
        abort_unless($vendor->isApproved(), 404);

        return view('storefront.vendors.show', [
            'vendor' => $vendor,
            'plugins' => $vendor->plugins()
                ->published()
                ->with(['vendor:id,name,slug', 'category:id,name,slug,icon', 'platforms'])
                ->orderByDesc('downloads_count')
                ->paginate(12),
        ]);
    }
}
