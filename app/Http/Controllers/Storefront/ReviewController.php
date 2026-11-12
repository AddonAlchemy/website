<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Actions\Reviews\SaveReview;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\StoreReviewRequest;
use App\Models\Plugin;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;

final class ReviewController extends Controller
{
    public function __construct(private readonly SaveReview $saveReview)
    {
    }

    public function store(StoreReviewRequest $request, Plugin $plugin): RedirectResponse
    {
        $this->authorize('review', $plugin);

        $this->saveReview->handle($request->user(), $plugin, $request->validated());

        return redirect(route('plugins.show', $plugin).'#reviews')->with('success', 'Danke für deine Bewertung!');
    }

    public function destroy(Review $review): RedirectResponse
    {
        $this->authorize('delete', $review);

        $this->saveReview->delete($review);

        return back()->with('success', 'Bewertung gelöscht.');
    }
}
