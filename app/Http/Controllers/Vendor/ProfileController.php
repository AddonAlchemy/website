<?php

declare(strict_types=1);

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\UpdateVendorProfileRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('vendor.profile', ['vendor' => $request->user()->vendor]);
    }

    public function update(UpdateVendorProfileRequest $request): RedirectResponse
    {
        $request->user()->vendor->update($request->validated());

        return back()->with('success', 'Studio-Profil gespeichert.');
    }
}
