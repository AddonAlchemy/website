<?php

declare(strict_types=1);

namespace App\Actions\Licensing;

use App\Enums\SubkeyStatus;
use App\Exceptions\MarketplaceException;
use App\Models\LicenseSubkey;
use App\Models\User;
use App\Services\KeyGenerator;
use App\Services\Settings;
use Illuminate\Support\Facades\DB;

final class ManageSubkeys
{
    public function __construct(
        private readonly KeyGenerator $keys,
        private readonly Settings $settings,
    ) {
    }

    /**
     * @param  list<int>  $pluginIds
     *
     * @throws MarketplaceException
     */
    public function create(User $user, ?string $label, array $pluginIds): LicenseSubkey
    {
        $licensed = $user->licenses()
            ->usable()
            ->whereIn('plugin_id', $pluginIds)
            ->limit($this->settings->subkeyMaxPlugins())
            ->pluck('plugin_id');

        if ($licensed->isEmpty()) {
            throw new MarketplaceException('Bitte mindestens ein lizenziertes Produkt auswählen.');
        }

        return DB::transaction(function () use ($user, $label, $licensed): LicenseSubkey {
            $subkey = $user->subkeys()->create([
                'key' => $this->keys->subkey(),
                'label' => $label,
            ]);

            $subkey->plugins()->sync($licensed->all());

            return $subkey;
        });
    }

    public function revoke(LicenseSubkey $subkey): void
    {
        $subkey->update([
            'status' => SubkeyStatus::Revoked,
            'revoked_at' => now(),
            'instance_id' => null,
        ]);
    }

    /**
     * @throws MarketplaceException
     */
    public function reactivate(LicenseSubkey $subkey): void
    {
        if ($subkey->isActive()) {
            throw new MarketplaceException('Dieser Key ist nicht widerrufen.');
        }

        $unlocksAt = $subkey->reactivatableAt($this->settings->subkeyLockDays());

        if ($unlocksAt !== null && $unlocksAt->isFuture()) {
            $days = (int) ceil(now()->diffInHours($unlocksAt) / 24);

            throw new MarketplaceException('Reaktivierung erst in '.$days.' Tag(en) möglich (Sperrfrist).');
        }

        $subkey->update([
            'status' => SubkeyStatus::Active,
            'revoked_at' => null,
            'instance_id' => null,
        ]);
    }
}
