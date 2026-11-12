<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LicenseCodeStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseCode extends Model
{
    protected $attributes = [
        'status' => LicenseCodeStatus::Unused->value,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => LicenseCodeStatus::class,
            'redeemed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Plugin, $this>
     */
    public function plugin(): BelongsTo
    {
        return $this->belongsTo(Plugin::class);
    }

    /**
     * @return BelongsTo<Vendor, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function redeemer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'redeemed_by');
    }
}
