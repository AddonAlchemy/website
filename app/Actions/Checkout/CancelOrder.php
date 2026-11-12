<?php

declare(strict_types=1);

namespace App\Actions\Checkout;

use App\Enums\OrderStatus;
use App\Exceptions\MarketplaceException;
use App\Models\Order;
use App\Services\Notifier;

final class CancelOrder
{
    public function __construct(private readonly Notifier $notifier)
    {
    }

    /**
     * @throws MarketplaceException
     */
    public function handle(Order $order): Order
    {
        if (! $order->isPending()) {
            throw new MarketplaceException('Nur offene Bestellungen können storniert werden.');
        }

        $order->update(['status' => OrderStatus::Cancelled, 'cancelled_at' => now()]);

        $this->notifier->user(
            $order->user,
            'Bestellung storniert',
            'Deine Bestellung '.$order->number.' wurde storniert.',
            'circle-x',
            route('account.orders.show', $order),
        );

        return $order;
    }
}
