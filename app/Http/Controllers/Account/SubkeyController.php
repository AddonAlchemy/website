<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Actions\Licensing\ManageSubkeys;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\StoreSubkeyRequest;
use App\Models\LicenseSubkey;
use App\Services\Settings;
use Illuminate\Http\RedirectResponse;

final class SubkeyController extends Controller
{
    public function __construct(private readonly ManageSubkeys $subkeys)
    {
    }

    public function store(StoreSubkeyRequest $request): RedirectResponse
    {
        $this->subkeys->create(
            $request->user(),
            $request->validated('label'),
            array_map('intval', $request->validated('plugins')),
        );

        return back()->with('success', 'Subuser-Key erstellt.');
    }

    public function revoke(LicenseSubkey $subkey, Settings $settings): RedirectResponse
    {
        $this->authorize('update', $subkey);

        $this->subkeys->revoke($subkey);

        return back()->with(
            'success',
            'Subuser-Key widerrufen. Der Zugriff ist sofort gesperrt. Reaktivierung erst nach '.$settings->subkeyLockDays().' Tagen möglich.',
        );
    }

    public function reactivate(LicenseSubkey $subkey): RedirectResponse
    {
        $this->authorize('update', $subkey);

        $this->subkeys->reactivate($subkey);

        return back()->with('success', 'Subuser-Key wurde reaktiviert.');
    }

    public function destroy(LicenseSubkey $subkey): RedirectResponse
    {
        $this->authorize('update', $subkey);

        $subkey->delete();

        return back()->with('success', 'Subuser-Key gelöscht.');
    }
}
