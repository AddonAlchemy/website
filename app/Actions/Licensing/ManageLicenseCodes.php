<?php

declare(strict_types=1);

namespace App\Actions\Licensing;

use App\Enums\LicenseCodeStatus;
use App\Enums\LicenseSource;
use App\Exceptions\MarketplaceException;
use App\Models\License;
use App\Models\LicenseCode;
use App\Models\Plugin;
use App\Models\User;
use App\Services\KeyGenerator;
use Illuminate\Support\Facades\DB;

final class ManageLicenseCodes
{
    public function __construct(
        private readonly KeyGenerator $keys,
        private readonly IssueLicense $issueLicense,
        private readonly RevokeLicense $revokeLicense,
    ) {
    }

    public function generate(Plugin $plugin, int $quantity, ?string $note): int
    {
        DB::transaction(function () use ($plugin, $quantity, $note): void {
            for ($i = 0; $i < $quantity; $i++) {
                $plugin->licenseCodes()->create([
                    'code' => $this->keys->licenseCode(),
                    'vendor_id' => $plugin->vendor_id,
                    'note' => $note,
                ]);
            }
        });

        return $quantity;
    }

    /**
     * @throws MarketplaceException
     */
    public function redeem(User $user, string $code): License
    {
        return DB::transaction(function () use ($user, $code): License {
            $licenseCode = LicenseCode::query()
                ->where('code', strtoupper(trim($code)))
                ->lockForUpdate()
                ->with('plugin')
                ->first();

            if ($licenseCode === null) {
                throw new MarketplaceException('Ungültiger Lizenz-Code.');
            }

            if ($licenseCode->status !== LicenseCodeStatus::Unused) {
                throw new MarketplaceException(match ($licenseCode->status) {
                    LicenseCodeStatus::Revoked => 'Dieser Code wurde widerrufen.',
                    default => 'Dieser Code wurde bereits eingelöst.',
                });
            }

            $license = $this->issueLicense->handle($user, $licenseCode->plugin, LicenseSource::Code, code: $licenseCode);

            $licenseCode->update([
                'status' => LicenseCodeStatus::Redeemed,
                'redeemed_by' => $user->id,
                'redeemed_at' => now(),
            ]);

            return $license;
        });
    }

    public function revoke(LicenseCode $code): void
    {
        DB::transaction(function () use ($code): void {
            $code->update(['status' => LicenseCodeStatus::Revoked]);

            License::query()
                ->where('license_code_id', $code->id)
                ->with('user')
                ->get()
                ->each(fn (License $license) => $this->revokeLicense->handle($license));
        });
    }
}
