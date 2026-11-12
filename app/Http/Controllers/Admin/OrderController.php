<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Checkout\CancelOrder;
use App\Actions\Checkout\FulfillOrder;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $status = OrderStatus::tryFrom((string) $request->query('status'));

        return view('admin.orders', [
            'orders' => Order::query()
                ->with(['user:id,name,email', 'items:id,order_id,plugin_name,unit_price_cents'])
                ->when($status, static fn ($query) => $query->where('status', $status))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'statuses' => OrderStatus::cases(),
            'status' => $status,
        ]);
    }

    public function fulfill(Order $order, FulfillOrder $fulfill): RedirectResponse
    {
        $fulfill->handle($order);

        return back()->with('success', 'Bestellung erfüllt – Lizenzen wurden ausgestellt.');
    }

    public function cancel(Order $order, CancelOrder $cancel): RedirectResponse
    {
        $cancel->handle($order);

        return back()->with('success', 'Bestellung storniert.');
    }
}
