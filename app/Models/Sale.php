<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SaleScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    protected $attributes = [
        'scope' => SaleScope::Shop->value,
        'is_active' => true,
        'vendor_share_percent' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'percent' => 'float',
            'scope' => SaleScope::class,
            'is_active' => 'boolean',
            'vendor_share_percent' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Vendor, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeRunning(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    public function appliesTo(Plugin $plugin): bool
    {
        return $this->scope === SaleScope::Shop || $this->vendor_id === $plugin->vendor_id;
    }
}
