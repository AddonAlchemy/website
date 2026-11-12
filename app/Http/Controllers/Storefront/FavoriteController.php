<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Plugin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class FavoriteController extends Controller
{
    public function __invoke(Request $request, Plugin $plugin): JsonResponse|RedirectResponse
    {
        $this->authorize('view', $plugin);

        $changes = $request->user()->favorites()->toggle([$plugin->id => ['created_at' => now()]]);
        $favorited = $changes['attached'] !== [];

        if ($request->expectsJson()) {
            return response()->json(['favorited' => $favorited]);
        }

        return back()->with('success', $favorited ? 'Zu deinen Favoriten hinzugefügt.' : 'Aus den Favoriten entfernt.');
    }
}
