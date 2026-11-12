<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Plugin;
use App\Models\Vendor;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;

final class HomeController extends Controller
{
    private const CARD_RELATIONS = ['vendor:id,name,slug', 'category:id,name,slug,icon', 'platforms'];

    public function __invoke(): View
    {
        return view('storefront.home', [
            'featured' => Plugin::query()->published()->where('is_featured', true)
                ->with(self::CARD_RELATIONS)->orderByDesc('downloads_count')->limit(6)->get(),
            'bestsellers' => Plugin::query()->published()
                ->with(self::CARD_RELATIONS)->orderByDesc('sales_count')->latest('published_at')->limit(3)->get(),
            'newest' => Plugin::query()->published()
                ->with(self::CARD_RELATIONS)->latest('published_at')->limit(6)->get(),
            'categories' => Category::query()
                ->withCount(['plugins' => static fn ($query) => $query->published()])
                ->orderBy('position')
                ->get(),
            'stats' => Cache::remember('storefront.stats', now()->addMinutes(10), static fn (): array => [
                'plugins' => Plugin::query()->published()->count(),
                'vendors' => Vendor::query()->approved()->count(),
                'downloads' => (int) Plugin::query()->published()->sum('downloads_count'),
            ]),
        ]);
    }
}
