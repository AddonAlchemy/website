<?php

declare(strict_types=1);

namespace App\Actions\Licensing;

use App\Data\LicenseApiResult;
use App\Models\License;
use App\Models\LicenseSubkey;
use App\Models\Plugin;

final class VerifyLicense
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(array $input): LicenseApiResult
    {
        $key = trim((string) ($input['key'] ?? ''));
        $productId = trim((string) ($input['product_id'] ?? ''));

        if ($key === '' || $productId === '' || strlen($key) > 64 || strlen($productId) > 64) {
            return new LicenseApiResult(400, ['valid' => false, 'genuine' => false, 'reason' => 'bad_request']);
        }

        $product = Plugin::query()->where('public_id', $productId)->value('name') ?? $productId;
        $license = License::query()->where('key', $key)->with('plugin:id,public_id')->first();

        if ($license !== null) {
            $reason = match (true) {
                $license->plugin->public_id !== $productId => 'product_not_licensed',
                $license->isExpired() => 'license_expired',
                ! $license->isUsable() => 'license_revoked',
                default => 'ok',
            };

            return $this->result('owner', $product, $reason);
        }

        $subkey = LicenseSubkey::query()->where('key', $key)->first();

        if ($subkey === null) {
            return new LicenseApiResult(404, ['valid' => false, 'genuine' => false, 'reason' => 'unknown_key']);
        }

        $plugin = $subkey->plugins()->where('public_id', $productId)->first();

        $reason = match (true) {
            ! $subkey->isActive() => 'revoked',
            $plugin === null => 'not_in_subuser',
            ! License::query()->usable()->where('user_id', $subkey->user_id)->where('plugin_id', $plugin->id)->exists() => 'owner_lost_product',
            default => 'ok',
        };

        return $this->result('subuser', $product, $reason);
    }

    private function result(string $mode, string $product, string $reason): LicenseApiResult
    {
        return new LicenseApiResult($reason === 'ok' ? 200 : 403, [
            'valid' => $reason === 'ok',
            'genuine' => true,
            'mode' => $mode,
            'product' => $product,
            'reason' => $reason,
        ]);
    }
}
