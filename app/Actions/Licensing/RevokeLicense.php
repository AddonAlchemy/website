<?php

declare(strict_types=1);

namespace App\Actions\Licensing;

use App\Enums\LicenseStatus;
use App\Models\License;
use Illuminate\Support\Facades\DB;

final class RevokeLicense
{
    public function handle(License $license): void
    {
        DB::transaction(function () use ($license): void {
            $license->update(['status' => LicenseStatus::Revoked, 'revoked_at' => now()]);
            $license->activations()->delete();

            DB::table('license_subkey_plugin')
                ->where('plugin_id', $license->plugin_id)
                ->whereIn('license_subkey_id', $license->user->subkeys()->select('id'))
                ->delete();
        });
    }
}
