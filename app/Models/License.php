<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LicenseSource;
use App\Enums\LicenseStatus;
use Database\Factories\LicenseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class License extends Model
{
    /** @use HasFactory<LicenseFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => LicenseStatus::Active->value,
        'source' => LicenseSource::Purchase->value,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => LicenseStatus::class,
            'source' => LicenseSource::class,
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
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
     * @return BelongsTo<Plugin, $this>
     */
    public function plugin(): BelongsTo
    {
        return $this->belongsTo(Plugin::class);
    }

    /**
     * @return HasMany<LicenseActivation, $this>
     */
    public function activations(): HasMany
    {
        return $this->hasMany(LicenseActivation::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeUsable(Builder $query): void
    {
        $query->where($this->qualifyColumn('status'), LicenseStatus::Active)
            ->where(fn (Builder $q) => $q
                ->whereNull($this->qualifyColumn('expires_at'))
                ->orWhere($this->qualifyColumn('expires_at'), '>', now()));
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isUsable(): bool
    {
        return $this->status === LicenseStatus::Active && ! $this->isExpired();
    }

    /**
     * @return list<string>
     */
    public function activeIps(): array
    {
        return $this->activations->pluck('ip')->unique()->values()->all();
    }
}
