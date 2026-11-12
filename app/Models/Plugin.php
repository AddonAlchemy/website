<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LicenseModel;
use App\Enums\PluginStatus;
use App\Enums\VersionStatus;
use Database\Factories\PluginFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class Plugin extends Model
{
    /** @use HasFactory<PluginFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => PluginStatus::Draft->value,
        'license_model' => LicenseModel::Perpetual->value,
        'price_cents' => 0,
        'is_featured' => false,
        'downloads_count' => 0,
        'sales_count' => 0,
        'reviews_count' => 0,
        'rating_average' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PluginStatus::class,
            'license_model' => LicenseModel::class,
            'is_featured' => 'boolean',
            'price_cents' => 'integer',
            'rating_average' => 'float',
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<Vendor, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * @return BelongsToMany<Platform, $this>
     */
    public function platforms(): BelongsToMany
    {
        return $this->belongsToMany(Platform::class)->orderBy('position');
    }

    /**
     * @return HasMany<PluginScreenshot, $this>
     */
    public function screenshots(): HasMany
    {
        return $this->hasMany(PluginScreenshot::class)->orderBy('position');
    }

    /**
     * @return HasMany<PluginVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(PluginVersion::class)->latest('id');
    }

    /**
     * @return HasMany<PluginVersion, $this>
     */
    public function releases(): HasMany
    {
        return $this->hasMany(PluginVersion::class)
            ->where('status', VersionStatus::Approved)
            ->latest('published_at')
            ->latest('id');
    }

    /**
     * @return HasOne<PluginVersion, $this>
     */
    public function latestRelease(): HasOne
    {
        return $this->hasOne(PluginVersion::class)->ofMany(
            ['published_at' => 'max', 'id' => 'max'],
            static fn (Builder $query) => $query->where('status', VersionStatus::Approved),
        );
    }

    /**
     * @return HasMany<Review, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * @return HasMany<License, $this>
     */
    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    /**
     * @return HasMany<LicenseCode, $this>
     */
    public function licenseCodes(): HasMany
    {
        return $this->hasMany(LicenseCode::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where($this->qualifyColumn('status'), PluginStatus::Approved);
    }

    public function isPublished(): bool
    {
        return $this->status === PluginStatus::Approved;
    }

    public function isFree(): bool
    {
        return $this->price_cents === 0;
    }

    public function iconUrl(): ?string
    {
        return $this->icon_path
            ? Storage::disk(config('marketplace.media.disk'))->url($this->icon_path)
            : null;
    }
}
