<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AnnouncementAudience;
use App\Enums\AnnouncementType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Announcement extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AnnouncementType::class,
            'audience' => AnnouncementAudience::class,
        ];
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function readers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'announcement_reads');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeVisibleTo(Builder $query, ?User $user): void
    {
        $query->whereIn('audience', AnnouncementAudience::visibleTo($user?->role));
    }
}
