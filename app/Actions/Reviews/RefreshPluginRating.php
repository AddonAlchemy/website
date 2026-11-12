<?php

declare(strict_types=1);

namespace App\Actions\Reviews;

use App\Models\Plugin;

final class RefreshPluginRating
{
    public function handle(Plugin $plugin): void
    {
        $stats = $plugin->reviews()
            ->toBase()
            ->selectRaw('COUNT(*) as total, COALESCE(AVG(rating), 0) as average')
            ->first();

        $plugin->update([
            'reviews_count' => (int) $stats->total,
            'rating_average' => round((float) $stats->average, 2),
        ]);
    }
}
