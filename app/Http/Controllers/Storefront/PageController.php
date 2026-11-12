<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Plugin;
use App\Services\Settings;
use Illuminate\Contracts\View\View;

final class PageController extends Controller
{
    private const LEGAL_PAGES = [
        'impressum' => ['legal_imprint', 'Impressum'],
        'datenschutz' => ['legal_privacy', 'Datenschutzerklärung'],
        'agb' => ['legal_terms', 'AGB'],
        'verkaeufer-bedingungen' => ['legal_vendor_terms', 'Verkäufer-Bedingungen'],
    ];

    public function legal(string $page, Settings $settings): View
    {
        [$key, $title] = self::LEGAL_PAGES[$page];

        return view('storefront.legal', [
            'title' => $title,
            'content' => $settings->get($key),
        ]);
    }

    public function licensing(Settings $settings): View
    {
        return view('storefront.licensing', [
            'sample' => Plugin::query()->published()->orderByDesc('downloads_count')->first(['public_id', 'name']),
            'maxIps' => $settings->maxIps(),
            'subkeyMaxPlugins' => $settings->subkeyMaxPlugins(),
            'subkeyLockDays' => $settings->subkeyLockDays(),
        ]);
    }

    /**
     * @return list<string>
     */
    public static function legalSlugs(): array
    {
        return array_keys(self::LEGAL_PAGES);
    }
}
