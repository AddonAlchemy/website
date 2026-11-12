<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SubkeyStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LicenseSubkey extends Model
{
    protected $attributes = [
        'status' => SubkeyStatus::Active->value,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SubkeyStatus::class,
            'revoked_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<Plugin, $this>
     */
    public function plugins(): BelongsToMany
    {
        return $this->belongsToMany(Plugin::class, 'license_subkey_plugin');
    }

    public function isActive(): bool
    {
        return $this->status === SubkeyStatus::Active;
    }

    public function reactivatableAt(int $lockDays): ?CarbonInterface
    {
        return $this->revoked_at?->copy()->addDays($lockDays);
    }
}
