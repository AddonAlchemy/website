<?php

declare(strict_types=1);

namespace App\Actions\Vendors;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Notifier;
use App\Support\UniqueSlug;
use Illuminate\Support\Facades\DB;

final class ApplyAsVendor
{
    public function __construct(private readonly Notifier $notifier)
    {
    }

    /**
     * @param  array{name: string, tagline?: ?string, website?: ?string}  $data
     */
    public function handle(User $user, array $data): Vendor
    {
        return DB::transaction(function () use ($user, $data): Vendor {
            $vendor = $user->vendor()->create([
                'name' => $data['name'],
                'slug' => UniqueSlug::for(Vendor::class, $data['name']),
                'tagline' => $data['tagline'] ?? null,
                'website' => $data['website'] ?? null,
            ]);

            if (! $user->isAdmin()) {
                $user->update(['role' => UserRole::Vendor]);
            }

            $this->notifier->admins(
                'Neue Vendor-Bewerbung',
                $vendor->name.' möchte Produkte auf dem Marktplatz anbieten.',
                'store',
                route('admin.vendors.index'),
            );

            return $vendor;
        });
    }
}
