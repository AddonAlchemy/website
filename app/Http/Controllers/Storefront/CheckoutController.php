<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Actions\Checkout\PlaceOrder;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\CheckoutRequest;
use Illuminate\Http\RedirectResponse;

final class CheckoutController extends Controller
{
    public function __invoke(CheckoutRequest $request, PlaceOrder $placeOrder): RedirectResponse
    {
        $order = $placeOrder->handle($request->user(), $request->input('payment_method'));

        return to_route('account.orders.show', $order)->with(
            'success',
            $order->status === OrderStatus::Fulfilled
                ? 'Bestellung abgeschlossen – deine Lizenzen sind freigeschaltet.'
                : 'Bestellung aufgegeben! Sie wird nach Zahlungseingang freigeschaltet.',
        );
    }
}
