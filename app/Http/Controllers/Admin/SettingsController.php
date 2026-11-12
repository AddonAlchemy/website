<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Services\Settings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class SettingsController extends Controller
{
    public function edit(Settings $settings): View
    {
        return view('admin.settings', ['values' => $settings->all()]);
    }

    public function update(UpdateSettingsRequest $request, Settings $settings): RedirectResponse
    {
        $settings->put($request->payload());

        return back()->with('success', 'Einstellungen gespeichert.');
    }
}
