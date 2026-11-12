<?php

declare(strict_types=1);

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\ReplyReviewRequest;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;

final class ReviewReplyController extends Controller
{
    public function __invoke(ReplyReviewRequest $request, Review $review): RedirectResponse
    {
        $this->authorize('reply', $review);

        $review->update([
            'vendor_reply' => $request->validated('reply'),
            'vendor_replied_at' => now(),
        ]);

        return back()->with('success', 'Antwort veröffentlicht.');
    }
}
