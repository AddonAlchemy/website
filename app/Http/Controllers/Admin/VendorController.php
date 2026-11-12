<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Vendors\UpdateVendorStatus;
use App\Enums\VendorStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateVendorStatusRequest;
use App\Models\Vendor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class VendorController extends Controller
{
    public function index(): View
    {
        return view('admin.vendors', [
            'applications' => Vendor::query()
                ->where('status', VendorStatus::Pending)
                ->with('user:id,name,email')
                ->oldest()
                ->get(),
            'vendors' => Vendor::query()
                ->where('status', '!=', VendorStatus::Pending)
                ->with('user:id,name,email')
                ->withCount('plugins')
                ->orderBy('name')
                ->paginate(20),
        ]);
    }

    public function update(UpdateVendorStatusRequest $request, Vendor $vendor, UpdateVendorStatus $update): RedirectResponse
    {
        $status = VendorStatus::from($request->validated('status'));

        $update->handle($vendor, $status, $request->validated('reason'));

        return back()->with('success', $vendor->name.': '.$status->label().'.');
    }
}
