<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    public $timestamps = false;

    /**
     * @return BelongsToMany<Plugin, $this>
     */
    public function plugins(): BelongsToMany
    {
        return $this->belongsToMany(Plugin::class);
    }
}
