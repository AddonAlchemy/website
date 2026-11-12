<?php

declare(strict_types=1);

namespace App\Actions\Licensing;

use App\Enums\LicenseSource;
use App\Enums\LicenseStatus;
use App\Models\License;
use App\Models\LicenseCode;
use App\Models\OrderItem;
use App\Models\Plugin;
use App\Models\User;
use App\Services\KeyGenerator;

final class IssueLicense
{
    public function __construct(private readonly KeyGenerator $keys)
    {
    }

    public function handle(
        User $user,
        Plugin $plugin,
        LicenseSource $source,
        ?OrderItem $orderItem = null,
        ?LicenseCode $code = null,
    ): License {
        $license = License::query()->firstOrNew(['user_id' => $user->id, 'plugin_id' => $plugin->id]);
        $renewalStart = $license->exists && $license->isUsable() && $license->expires_at !== null
            ? $license->expires_at
            : now();

        $license->fill([
            'key' => $license->key ?? $this->keys->licenseKey(),
            'source' => $source,
            'status' => LicenseStatus::Active,
            'order_item_id' => $orderItem?->id ?? $license->order_item_id,
            'license_code_id' => $code?->id ?? $license->license_code_id,
            'expires_at' => $plugin->license_model->expiresFrom($renewalStart),
            'revoked_at' => null,
        ])->save();

        return $license;
    }
}
