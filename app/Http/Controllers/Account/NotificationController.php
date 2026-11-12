<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class NotificationController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        $user->unreadNotifications()->update(['read_at' => now()]);

        DB::table('announcement_reads')->insertOrIgnore(
            Announcement::query()
                ->visibleTo($user)
                ->pluck('id')
                ->map(static fn (int $id): array => ['announcement_id' => $id, 'user_id' => $user->id])
                ->all(),
        );

        return back()->with('success', 'Alle Benachrichtigungen als gelesen markiert.');
    }
}
