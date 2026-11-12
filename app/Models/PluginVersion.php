<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VersionStatus;
use Database\Factories\PluginVersionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PluginVersion extends Model
{
    /** @use HasFactory<PluginVersionFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => VersionStatus::Pending->value,
        'downloads_count' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => VersionStatus::class,
            'game_versions' => 'array',
            'file_size' => 'integer',
            'reviewed_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /**
     * @return BelongsTo<Plugin, $this>
     */
    public function plugin(): BelongsTo
    {
        return $this->belongsTo(Plugin::class);
    }

    /**
     * @return BelongsToMany<Platform, $this>
     */
    public function platforms(): BelongsToMany
    {
        return $this->belongsToMany(Platform::class)->orderBy('position');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeApproved(Builder $query): void
    {
        $query->where('status', VersionStatus::Approved);
    }

    public function isApproved(): bool
    {
        return $this->status === VersionStatus::Approved;
    }

    public function humanFileSize(): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = (float) $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return number_format($size, $unit === 0 ? 0 : 1, ',', '.').' '.$units[$unit];
    }
}
