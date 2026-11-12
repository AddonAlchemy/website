<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class OrderController extends Controller
{
    public function index(Request $request): View
    {
        return view('account.orders.index', [
            'orders' => $request->user()->orders()
                ->with(['items:id,order_id,plugin_name', 'invoice:id,order_id,number'])
                ->latest()
                ->paginate(15),
        ]);
    }

    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        return view('account.orders.show', [
            'order' => $order->load(['items.plugin:id,slug,name', 'invoice']),
        ]);
    }

    public function invoice(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);

        return view('account.orders.invoice', [
            'invoice' => $invoice->load(['order.items']),
        ]);
    }
}
