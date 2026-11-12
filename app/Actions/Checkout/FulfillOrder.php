<?php

declare(strict_types=1);

namespace App\Actions\Checkout;

use App\Actions\Licensing\IssueLicense;
use App\Enums\LicenseSource;
use App\Enums\OrderStatus;
use App\Exceptions\MarketplaceException;
use App\Models\Order;
use App\Models\Vendor;
use App\Services\KeyGenerator;
use App\Services\Notifier;
use App\Services\SettlementCalculator;
use Illuminate\Support\Facades\DB;

final class FulfillOrder
{
    public function __construct(
        private readonly IssueLicense $issueLicense,
        private readonly SettlementCalculator $settlements,
        private readonly KeyGenerator $keys,
        private readonly Notifier $notifier,
    ) {
    }

    /**
     * @throws MarketplaceException
     */
    public function handle(Order $order): Order
    {
        return DB::transaction(function () use ($order): Order {
            $order = Order::query()
                ->lockForUpdate()
                ->with(['items.plugin', 'user'])
                ->findOrFail($order->id);

            if (! $order->isPending()) {
                throw new MarketplaceException('Diese Bestellung ist bereits abgeschlossen oder storniert.');
            }

            foreach ($order->items as $item) {
                if ($item->plugin === null) {
                    continue;
                }

                $this->issueLicense->handle($order->user, $item->plugin, LicenseSource::Purchase, $item);
                $item->plugin->increment('sales_count');
            }

            $order->update(['status' => OrderStatus::Fulfilled, 'fulfilled_at' => now()]);

            $order->invoice()->create([
                'number' => sprintf('RE-%s-%06d', now()->format('Y'), $order->id),
                'user_id' => $order->user_id,
                'billing' => $order->billing,
                'total_cents' => $order->total_cents,
                'issued_at' => now(),
            ]);

            $this->createSettlements($order);

            $this->notifier->user(
                $order->user,
                'Produkte freigeschaltet',
                'Deine Bestellung '.$order->number.' wurde freigeschaltet – die Lizenzen findest du in deiner Lizenzverwaltung.',
                'key-round',
                route('account.licenses.index'),
            );

            return $order;
        });
    }

    private function createSettlements(Order $order): void
    {
        $breakdowns = $this->settlements->forOrder($order)->filter(
            static fn ($breakdown): bool => $breakdown->grossCents > 0,
        );

        $vendors = Vendor::query()->with('user')->findMany($breakdowns->keys())->keyBy('id');

        foreach ($breakdowns as $vendorId => $breakdown) {
            $settlement = $order->settlements()->create([
                'number' => $this->keys->settlementNumber(),
                'vendor_id' => $vendorId,
                'units' => $breakdown->units,
                'gross_cents' => $breakdown->grossCents,
                'vendor_discount_cents' => $breakdown->vendorDiscountCents,
                'platform_discount_cents' => $breakdown->platformDiscountCents,
                'payment_fee_cents' => $breakdown->paymentFeeCents,
                'platform_fee_cents' => $breakdown->platformFeeCents,
                'net_cents' => $breakdown->netCents(),
            ]);

            $this->notifier->vendor(
                $vendors[$vendorId],
                'Neue Abrechnung',
                'Für Bestellung '.$order->number.' wurde eine Abrechnung erstellt (Status: ausstehend).',
                'receipt',
                route('vendor.settlements.show', $settlement),
            );
        }
    }
}
