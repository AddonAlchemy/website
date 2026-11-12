<?php

declare(strict_types=1);

namespace App\Actions\Licensing;

use App\Data\LicenseApiResult;
use App\Models\License;
use Illuminate\Support\Facades\DB;

final class DeactivateInstance
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(array $input): LicenseApiResult
    {
        $key = trim((string) ($input['key'] ?? ''));
        $instanceId = trim((string) ($input['instance_id'] ?? ''));

        if ($key === '' || $instanceId === '' || strlen($key) > 64 || strlen($instanceId) > 64) {
            return new LicenseApiResult(400, ['status' => 'error', 'reason' => 'bad_request']);
        }

        return DB::transaction(function () use ($key, $instanceId): LicenseApiResult {
            $license = License::query()->where('key', $key)->lockForUpdate()->first();

            if ($license === null) {
                return new LicenseApiResult(404, ['status' => 'error', 'reason' => 'unknown_key']);
            }

            $removed = $license->activations()->where('instance_id', $instanceId)->delete();

            return LicenseApiResult::ok([
                'status' => 'ok',
                'removed' => $removed,
                'active_ips' => $license->activations()->distinct()->pluck('ip')->all(),
            ]);
        });
    }
}
