<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PluginStatus;
use App\Enums\SettlementStatus;
use App\Enums\VersionStatus;
use App\Models\Download;
use App\Models\PluginVersion;
use App\Models\Settlement;
use App\Models\Vendor;
use App\Support\DailySeries;

final class VendorStatistics
{
    private const DAYS = 30;

    /**
     * @return array<string, mixed>
     */
    public function for(Vendor $vendor): array
    {
        $plugins = $vendor->plugins()
            ->toBase()
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(sales_count), 0) as sales, COALESCE(SUM(downloads_count), 0) as downloads')
            ->selectRaw('COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) as published', [PluginStatus::Approved->value])
            ->first();

        $earnings = $vendor->settlements()
            ->toBase()
            ->selectRaw('status, COALESCE(SUM(net_cents), 0) as net')
            ->groupBy('status')
            ->pluck('net', 'status');

        $since = now()->subDays(self::DAYS - 1)->startOfDay();

        $downloads = Download::query()
            ->whereIn('plugin_id', $vendor->plugins()->select('id'))
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->toBase()
            ->pluck('total', 'day');

        $revenue = Settlement::query()
            ->where('vendor_id', $vendor->id)
            ->where('status', '!=', SettlementStatus::Cancelled)
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as day, SUM(net_cents) as total')
            ->groupBy('day')
            ->toBase()
            ->pluck('total', 'day');

        return [
            'plugins' => (int) $plugins->total,
            'published' => (int) $plugins->published,
            'sales' => (int) $plugins->sales,
            'downloads' => (int) $plugins->downloads,
            'pendingCents' => (int) ($earnings[SettlementStatus::Pending->value] ?? 0),
            'availableCents' => (int) ($earnings[SettlementStatus::Available->value] ?? 0),
            'paidOutCents' => (int) ($earnings[SettlementStatus::PaidOut->value] ?? 0),
            'pendingVersions' => PluginVersion::query()
                ->whereIn('plugin_id', $vendor->plugins()->select('id'))
                ->where('status', VersionStatus::Pending)
                ->count(),
            'downloadSeries' => DailySeries::fill($downloads, self::DAYS),
            'revenueSeries' => DailySeries::fill($revenue, self::DAYS, 100),
            'topPlugins' => $vendor->plugins()
                ->orderByDesc('sales_count')
                ->orderByDesc('downloads_count')
                ->limit(5)
                ->get(['id', 'name', 'slug', 'status', 'sales_count', 'downloads_count', 'rating_average', 'reviews_count']),
        ];
    }
}
