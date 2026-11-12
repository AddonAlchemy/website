<?php

declare(strict_types=1);

namespace App\Http\Controllers\Vendor;

use App\Actions\Licensing\ManageLicenseCodes;
use App\Enums\LicenseCodeStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\GenerateCodesRequest;
use App\Models\LicenseCode;
use App\Models\Plugin;
use Illuminate\Http\RedirectResponse;

final class LicenseCodeController extends Controller
{
    public function __construct(private readonly ManageLicenseCodes $codes)
    {
    }

    public function store(GenerateCodesRequest $request, Plugin $plugin): RedirectResponse
    {
        $this->authorize('update', $plugin);

        $quantity = $this->codes->generate($plugin, $request->integer('quantity'), $request->validated('note'));

        return redirect(route('vendor.plugins.edit', $plugin).'#codes')->with('success', $quantity.' Lizenz-Code(s) erstellt.');
    }

    public function revoke(LicenseCode $code): RedirectResponse
    {
        $this->authorize('update', $code);

        $this->codes->revoke($code);

        return back()->with('success', 'Lizenz-Code widerrufen.');
    }

    public function destroy(LicenseCode $code): RedirectResponse
    {
        $this->authorize('update', $code);

        if ($code->status === LicenseCodeStatus::Redeemed) {
            return back()->with('error', 'Eingelöste Codes müssen zuerst widerrufen werden.');
        }

        $code->delete();

        return back()->with('success', 'Lizenz-Code gelöscht.');
    }
}
