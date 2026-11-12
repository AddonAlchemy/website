<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Actions\Licensing\ManageLicenseCodes;
use App\Actions\Licensing\RotateLicenseKey;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\RedeemCodeRequest;
use App\Models\License;
use App\Models\LicenseActivation;
use App\Services\Settings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class LicenseController extends Controller
{
    public function index(Request $request, Settings $settings): View
    {
        $user = $request->user();

        return view('account.licenses', [
            'licenses' => $user->licenses()
                ->with(['plugin:id,name,slug,public_id,icon_path,latest_version,price_cents', 'plugin.latestRelease', 'activations'])
                ->latest()
                ->get(),
            'subkeys' => $user->subkeys()->with('plugins:id,name')->latest()->get(),
            'maxIps' => $settings->maxIps(),
            'subkeyMaxPlugins' => $settings->subkeyMaxPlugins(),
            'subkeyLockDays' => $settings->subkeyLockDays(),
        ]);
    }

    public function redeem(RedeemCodeRequest $request, ManageLicenseCodes $codes): RedirectResponse
    {
        $license = $codes->redeem($request->user(), $request->validated('code'));

        return back()->with('success', 'Lizenz-Code eingelöst – '.$license->plugin->name.' wurde freigeschaltet!');
    }

    public function rotate(License $license, RotateLicenseKey $rotate): RedirectResponse
    {
        $this->authorize('update', $license);

        $rotate->handle($license);

        return back()->with('success', 'Neuer Lizenzschlüssel erstellt. Alle bisherigen Aktivierungen wurden zurückgesetzt.');
    }

    public function releaseIp(Request $request, License $license): RedirectResponse
    {
        $this->authorize('update', $license);

        $license->activations()->where('ip', $request->validate(['ip' => ['required', 'ip']])['ip'])->delete();

        return back()->with('success', 'IP-Adresse wurde freigegeben.');
    }

    public function destroyActivation(LicenseActivation $activation): RedirectResponse
    {
        $this->authorize('update', $activation->license);

        $activation->delete();

        return back()->with('success', 'Instanz wurde entfernt.');
    }
}
