<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PluginStatus;
use App\Enums\SettlementStatus;
use App\Enums\VendorStatus;
use App\Enums\VersionStatus;
use App\Models\Download;
use App\Models\LicenseActivation;
use App\Models\Order;
use App\Models\Plugin;
use App\Models\PluginVersion;
use App\Models\Settlement;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Vendor;
use App\Support\DailySeries;

final class MarketplaceStatistics
{
    private const DAYS = 30;

    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        $since = now()->subDays(self::DAYS - 1)->startOfDay();

        $orders = Order::query()
            ->toBase()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('COALESCE(SUM(CASE WHEN status = ? THEN total_cents ELSE 0 END), 0) as revenue', [OrderStatus::Fulfilled->value])
            ->selectRaw('COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) as pending', [OrderStatus::Pending->value])
            ->first();

        $revenue = Order::query()
            ->where('status', OrderStatus::Fulfilled)
            ->where('fulfilled_at', '>=', $since)
            ->selectRaw('DATE(fulfilled_at) as day, SUM(total_cents) as total')
            ->groupBy('day')
            ->toBase()
            ->pluck('total', 'day');

        $signups = User::query()
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->toBase()
            ->pluck('total', 'day');

        return [
            'users' => User::query()->count(),
            'vendors' => Vendor::query()->approved()->count(),
            'pendingVendors' => Vendor::query()->where('status', VendorStatus::Pending)->count(),
            'plugins' => Plugin::query()->published()->count(),
            'pendingPlugins' => Plugin::query()->where('status', PluginStatus::Pending)->count(),
            'pendingVersions' => PluginVersion::query()
                ->where('status', VersionStatus::Pending)
                ->whereHas('plugin', static fn ($query) => $query->where('status', PluginStatus::Approved))
                ->count(),
            'orders' => (int) $orders->total,
            'pendingOrders' => (int) $orders->pending,
            'revenueCents' => (int) $orders->revenue,
            'platformFeeCents' => (int) Settlement::query()
                ->where('status', '!=', SettlementStatus::Cancelled)
                ->sum('platform_fee_cents'),
            'downloads' => Download::query()->count(),
            'activeInstances' => LicenseActivation::query()->where('last_seen_at', '>=', now()->subDay())->count(),
            'openTickets' => Ticket::query()->whereNull('vendor_id')->where('status', 'open')->count(),
            'revenueSeries' => DailySeries::fill($revenue, self::DAYS, 100),
            'signupSeries' => DailySeries::fill($signups, self::DAYS),
            'topPlugins' => Plugin::query()
                ->published()
                ->with('vendor:id,name,slug')
                ->orderByDesc('sales_count')
                ->orderByDesc('downloads_count')
                ->limit(5)
                ->get(),
        ];
    }
}
