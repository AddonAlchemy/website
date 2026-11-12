<?php

declare(strict_types=1);

namespace App\Actions\Reviews;

use App\Models\Plugin;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class SaveReview
{
    public function __construct(private readonly RefreshPluginRating $refreshRating)
    {
    }

    /**
     * @param  array{rating: int, title?: ?string, body: string}  $data
     */
    public function handle(User $user, Plugin $plugin, array $data): Review
    {
        return DB::transaction(function () use ($user, $plugin, $data): Review {
            $review = Review::query()->updateOrCreate(
                ['plugin_id' => $plugin->id, 'user_id' => $user->id],
                ['rating' => $data['rating'], 'title' => $data['title'] ?? null, 'body' => $data['body']],
            );

            $this->refreshRating->handle($plugin);

            return $review;
        });
    }

    public function delete(Review $review): void
    {
        DB::transaction(function () use ($review): void {
            $review->delete();
            $this->refreshRating->handle($review->plugin);
        });
    }
}
