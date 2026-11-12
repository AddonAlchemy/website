<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Plugin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class LegacyRedirectController extends Controller
{
    public function product(Request $request): RedirectResponse
    {
        $plugin = Plugin::query()
            ->published()
            ->where('public_id', (string) $request->query('id'))
            ->firstOrFail();

        return to_route('plugins.show', $plugin, 301);
    }

    public function shop(Request $request): RedirectResponse
    {
        return to_route('plugins.index', array_filter(['q' => $request->query('q')]), 301);
    }
}
