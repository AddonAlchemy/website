<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Plugin;
use App\Services\Cart;
use App\Services\Settings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class CartController extends Controller
{
    public function __construct(private readonly Cart $cart)
    {
    }

    public function show(Settings $settings): View
    {
        return view('storefront.cart', [
            'lines' => $this->cart->lines(),
            'totalCents' => $this->cart->totalCents(),
            'originalTotalCents' => $this->cart->originalTotalCents(),
            'paymentMethods' => $settings->paymentMethods(),
        ]);
    }

    public function store(Plugin $plugin): RedirectResponse
    {
        abort_unless($plugin->isPublished(), 404);

        $this->cart->add($plugin);

        return back()->with('success', $plugin->name.' wurde zum Warenkorb hinzugefügt.');
    }

    public function destroy(Plugin $plugin): RedirectResponse
    {
        $this->cart->remove($plugin);

        return to_route('cart.show')->with('success', 'Artikel entfernt.');
    }

    public function clear(): RedirectResponse
    {
        $this->cart->clear();

        return to_route('cart.show')->with('success', 'Warenkorb geleert.');
    }
}
