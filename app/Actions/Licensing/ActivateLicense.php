<?php

declare(strict_types=1);

namespace App\Actions\Licensing;

use App\Data\LicenseApiResult;
use App\Models\License;
use App\Models\LicenseSubkey;
use App\Models\Plugin;
use App\Services\KeyGenerator;
use App\Services\Settings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ActivateLicense
{
    public function __construct(
        private readonly Settings $settings,
        private readonly KeyGenerator $keys,
    ) {
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(array $input, string $ip): LicenseApiResult
    {
        $key = trim((string) ($input['key'] ?? ''));
        $productId = trim((string) ($input['product_id'] ?? ''));
        $instanceId = trim((string) ($input['instance_id'] ?? ''));
        $name = Str::limit(trim((string) ($input['name'] ?? '')), 80, '');

        if ($key === '' || strlen($key) > 64 || $productId === '' || strlen($productId) > 64) {
            return LicenseApiResult::invalid(400, 'bad_request', 'key und product_id sind erforderlich.');
        }

        if ($instanceId !== '' && (strlen($instanceId) > 64 || preg_match('/^[A-Za-z0-9\-]+$/', $instanceId) !== 1)) {
            return LicenseApiResult::invalid(400, 'bad_instance_id');
        }

        return DB::transaction(function () use ($key, $productId, $instanceId, $name, $ip): LicenseApiResult {
            $license = License::query()
                ->where('key', $key)
                ->lockForUpdate()
                ->with(['plugin:id,public_id,name', 'activations'])
                ->first();

            if ($license !== null) {
                return $this->activateOwner($license, $productId, $instanceId, $name, $ip);
            }

            $subkey = LicenseSubkey::query()->where('key', $key)->lockForUpdate()->first();

            if ($subkey !== null) {
                return $this->activateSubkey($subkey, $productId, $instanceId);
            }

            return LicenseApiResult::invalid(404, 'unknown_key', 'Lizenz- oder Subuser-Key nicht gefunden.');
        });
    }

    private function activateOwner(License $license, string $productId, string $instanceId, string $name, string $ip): LicenseApiResult
    {
        if ($license->plugin->public_id !== $productId) {
            return LicenseApiResult::invalid(403, 'product_not_licensed', 'Dieses Produkt ist für die Lizenz nicht freigeschaltet.');
        }

        if (! $license->isUsable()) {
            return $license->isExpired()
                ? LicenseApiResult::invalid(403, 'license_expired', 'Diese Lizenz ist abgelaufen.')
                : LicenseApiResult::invalid(403, 'license_revoked', 'Diese Lizenz wurde widerrufen.');
        }

        $maxIps = $this->settings->maxIps();
        $activeIps = $license->activeIps();

        if (! in_array($ip, $activeIps, true)) {
            if (count($activeIps) >= $maxIps) {
                return LicenseApiResult::invalid(
                    403,
                    'ip_limit_reached',
                    'Diese Lizenz ist bereits auf '.$maxIps.' IP-Adressen aktiv.',
                    ['active_ips' => $activeIps],
                );
            }

            $activeIps[] = $ip;
        }

        $instanceId = $instanceId !== '' ? $instanceId : $this->keys->instanceId();
        $activation = $license->activations()->firstOrNew(['instance_id' => $instanceId]);
        $activation->fill([
            'ip' => $ip,
            'name' => $name !== '' ? $name : $activation->name,
            'last_seen_at' => now(),
        ])->save();

        return LicenseApiResult::ok([
            'status' => 'valid',
            'mode' => 'owner',
            'instance_id' => $instanceId,
            'ip' => $ip,
            'product' => $license->plugin->name,
            'active_ips' => $activeIps,
            'ip_limit' => $maxIps,
            'expires_at' => $license->expires_at?->toIso8601String(),
        ]);
    }

    private function activateSubkey(LicenseSubkey $subkey, string $productId, string $instanceId): LicenseApiResult
    {
        if (! $subkey->isActive()) {
            return LicenseApiResult::invalid(403, 'subuser_revoked', 'Dieser Subuser-Key wurde widerrufen.');
        }

        /** @var Plugin|null $plugin */
        $plugin = $subkey->plugins()->where('public_id', $productId)->first();

        if ($plugin === null) {
            return LicenseApiResult::invalid(403, 'product_not_in_subuser', 'Dieses Produkt ist nicht Teil des Subuser-Keys.');
        }

        $ownerLicensed = License::query()
            ->usable()
            ->where('user_id', $subkey->user_id)
            ->where('plugin_id', $plugin->id)
            ->exists();

        if (! $ownerLicensed) {
            return LicenseApiResult::invalid(403, 'owner_lost_product', 'Der Lizenzinhaber besitzt dieses Produkt nicht mehr.');
        }

        if (filled($subkey->instance_id) && $instanceId !== '' && ! hash_equals($subkey->instance_id, $instanceId)) {
            return LicenseApiResult::invalid(
                403,
                'subuser_bound_other_instance',
                'Dieser Subuser-Key ist bereits an eine andere Instanz gebunden.',
            );
        }

        $boundInstance = filled($subkey->instance_id)
            ? $subkey->instance_id
            : ($instanceId !== '' ? $instanceId : $this->keys->instanceId());

        $subkey->update(['instance_id' => $boundInstance, 'last_seen_at' => now()]);

        return LicenseApiResult::ok([
            'status' => 'valid',
            'mode' => 'subuser',
            'instance_id' => $boundInstance,
            'product' => $plugin->name,
            'note' => 'Subuser: an eine Instanz gebunden, IP-unabhängig.',
        ]);
    }
}
