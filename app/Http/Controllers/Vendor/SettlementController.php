<?php

declare(strict_types=1);

namespace App\Http\Controllers\Vendor;

use App\Enums\SettlementStatus;
use App\Http\Controllers\Controller;
use App\Models\Settlement;
use App\Services\Settings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class SettlementController extends Controller
{
    public function payouts(Request $request, Settings $settings): View
    {
        $vendor = $request->user()->vendor;

        $totals = $vendor->settlements()
            ->where('status', '!=', SettlementStatus::Cancelled)
            ->toBase()
            ->selectRaw('COALESCE(SUM(units), 0) as units, COALESCE(SUM(gross_cents), 0) as gross')
            ->selectRaw('COALESCE(SUM(vendor_discount_cents), 0) as vendor_discount, COALESCE(SUM(payment_fee_cents), 0) as payment_fee')
            ->selectRaw('COALESCE(SUM(platform_fee_cents), 0) as platform_fee, COALESCE(SUM(net_cents), 0) as net')
            ->first();

        return view('vendor.payouts', [
            'totals' => $totals,
            'balances' => $vendor->settlements()->toBase()
                ->selectRaw('status, COALESCE(SUM(net_cents), 0) as net')
                ->groupBy('status')
                ->pluck('net', 'status'),
            'products' => DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('order_items.vendor_id', $vendor->id)
                ->where('orders.status', 'fulfilled')
                ->groupBy('order_items.plugin_public_id', 'order_items.plugin_name')
                ->orderByDesc('revenue')
                ->get([
                    'order_items.plugin_public_id',
                    'order_items.plugin_name',
                    DB::raw('COUNT(*) as units'),
                    DB::raw('SUM(order_items.original_price_cents) as gross'),
                    DB::raw('SUM(order_items.unit_price_cents) as revenue'),
                ]),
            'platformFeePercent' => $settings->platformFeePercent(),
            'paymentMethods' => $settings->paymentMethods(),
        ]);
    }

    public function index(Request $request): View
    {
        return view('vendor.settlements.index', [
            'settlements' => $request->user()->vendor->settlements()
                ->with('order:id,number,payment_method_label')
                ->latest()
                ->paginate(20),
        ]);
    }

    public function show(Settlement $settlement): View
    {
        $this->authorize('view', $settlement);

        return view('vendor.settlements.show', [
            'settlement' => $settlement->load(['vendor.user:id,email', 'order.items']),
        ]);
    }
}
