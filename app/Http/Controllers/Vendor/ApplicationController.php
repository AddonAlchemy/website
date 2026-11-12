<?php

declare(strict_types=1);

namespace App\Http\Controllers\Vendor;

use App\Actions\Vendors\ApplyAsVendor;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\VendorApplicationRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ApplicationController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if ($request->user()->vendor !== null) {
            return to_route('vendor.dashboard');
        }

        return view('vendor.apply');
    }

    public function store(VendorApplicationRequest $request, ApplyAsVendor $apply): RedirectResponse
    {
        if ($request->user()->vendor !== null) {
            return to_route('vendor.dashboard');
        }

        $apply->handle($request->user(), $request->safe()->only(['name', 'tagline', 'website']));

        return to_route('vendor.dashboard')->with('success', 'Bewerbung eingereicht. Wir prüfen dein Studio zeitnah.');
    }
}
