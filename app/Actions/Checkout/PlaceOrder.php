<?php

declare(strict_types=1);

namespace App\Actions\Checkout;

use App\Data\CartLine;
use App\Enums\LicenseModel;
use App\Exceptions\MarketplaceException;
use App\Models\Order;
use App\Models\User;
use App\Services\Cart;
use App\Services\KeyGenerator;
use App\Services\Notifier;
use App\Services\Settings;
use Illuminate\Support\Facades\DB;

final class PlaceOrder
{
    public function __construct(
        private readonly Cart $cart,
        private readonly Settings $settings,
        private readonly KeyGenerator $keys,
        private readonly FulfillOrder $fulfillOrder,
        private readonly Notifier $notifier,
    ) {
    }

    /**
     * @throws MarketplaceException
     */
    public function handle(User $user, ?string $paymentMethodId): Order
    {
        $lines = $this->cart->lines();

        if ($lines->isEmpty()) {
            throw new MarketplaceException('Dein Warenkorb ist leer.');
        }

        $this->assertNoDuplicateLicenses($user, $lines->all());

        $method = $this->settings->paymentMethod($paymentMethodId);

        $order = DB::transaction(function () use ($user, $lines, $method): Order {
            $order = $user->orders()->create([
                'number' => $this->keys->orderNumber(),
                'payment_method' => $method->id,
                'payment_method_label' => $method->label,
                'payment_fee_percent' => $method->percent,
                'payment_fee_fixed_cents' => $method->fixedCents,
                'subtotal_cents' => $this->cart->originalTotalCents(),
                'discount_cents' => $this->cart->originalTotalCents() - $this->cart->totalCents(),
                'total_cents' => $this->cart->totalCents(),
                'billing' => $user->billingSnapshot(),
            ]);

            $order->items()->createMany($lines->map(static fn (CartLine $line): array => [
                'plugin_id' => $line->plugin->id,
                'vendor_id' => $line->plugin->vendor_id,
                'sale_id' => $line->quote->sale?->id,
                'plugin_name' => $line->plugin->name,
                'plugin_public_id' => $line->plugin->public_id,
                'license_model' => $line->plugin->license_model,
                'original_price_cents' => $line->quote->originalCents,
                'discount_cents' => $line->quote->discountCents(),
                'unit_price_cents' => $line->quote->finalCents,
                'vendor_discount_share' => $line->quote->sale?->vendor_share_percent ?? 100,
            ])->all());

            return $order;
        });

        $this->cart->clear();

        $this->notifier->user(
            $user,
            'Bestellung eingegangen',
            'Deine Bestellung '.$order->number.' über '.money($order->total_cents).' wird bearbeitet.',
            'receipt',
            route('account.orders.show', $order),
        );

        if ($order->total_cents === 0 || config('marketplace.checkout.auto_capture')) {
            return $this->fulfillOrder->handle($order);
        }

        return $order;
    }

    /**
     * @param  array<int, CartLine>  $lines
     *
     * @throws MarketplaceException
     */
    private function assertNoDuplicateLicenses(User $user, array $lines): void
    {
        $perpetualIds = collect($lines)
            ->filter(static fn (CartLine $line): bool => $line->plugin->license_model === LicenseModel::Perpetual)
            ->map(static fn (CartLine $line): int => $line->plugin->id);

        $owned = $user->licenses()
            ->usable()
            ->whereIn('plugin_id', $perpetualIds)
            ->with('plugin:id,name')
            ->first();

        if ($owned !== null) {
            throw new MarketplaceException('Du besitzt bereits eine Lizenz für '.$owned->plugin->name.'.');
        }
    }
}
