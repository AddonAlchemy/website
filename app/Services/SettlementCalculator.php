<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\SettlementBreakdown;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Collection;

final class SettlementCalculator
{
    public function __construct(private readonly Settings $settings)
    {
    }

    /**
     * @return Collection<int, SettlementBreakdown>
     */
    public function forOrder(Order $order): Collection
    {
        $platformRate = $this->settings->platformFeePercent() / 100;
        $orderPaymentFee = $order->paymentFeeCents();
        $total = $order->total_cents;

        return $order->items
            ->filter(static fn (OrderItem $item): bool => $item->vendor_id !== null)
            ->groupBy('vendor_id')
            ->map(static function (Collection $items) use ($platformRate, $orderPaymentFee, $total): SettlementBreakdown {
                $gross = 0;
                $vendorDiscount = 0.0;
                $platformDiscount = 0.0;
                $paymentFee = 0.0;
                $platformFee = 0.0;

                foreach ($items as $item) {
                    $share = min(100, max(0, $item->vendor_discount_share)) / 100;
                    $itemVendorDiscount = $item->discount_cents * $share;
                    $itemPaymentFee = $total > 0 ? $orderPaymentFee * ($item->unit_price_cents / $total) : 0.0;

                    $gross += $item->original_price_cents;
                    $vendorDiscount += $itemVendorDiscount;
                    $platformDiscount += $item->discount_cents * (1 - $share);
                    $paymentFee += $itemPaymentFee;
                    $platformFee += ($item->original_price_cents - $itemVendorDiscount - $itemPaymentFee) * $platformRate;
                }

                return new SettlementBreakdown(
                    units: $items->count(),
                    grossCents: $gross,
                    vendorDiscountCents: (int) round($vendorDiscount),
                    platformDiscountCents: (int) round($platformDiscount),
                    paymentFeeCents: (int) round($paymentFee),
                    platformFeeCents: max(0, (int) round($platformFee)),
                );
            });
    }
}
