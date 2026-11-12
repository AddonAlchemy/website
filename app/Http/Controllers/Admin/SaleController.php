<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveSaleRequest;
use App\Models\Sale;
use App\Models\Vendor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class SaleController extends Controller
{
    public function index(): View
    {
        return view('admin.sales', [
            'sales' => Sale::query()->with('vendor:id,name')->latest()->get(),
            'vendors' => Vendor::query()->approved()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(SaveSaleRequest $request): RedirectResponse
    {
        Sale::query()->create($request->payload());

        return back()->with('success', 'Sale-Aktion erstellt.');
    }

    public function toggle(Sale $sale): RedirectResponse
    {
        $sale->update(['is_active' => ! $sale->is_active]);

        return back()->with('success', 'Sale-Status geändert.');
    }

    public function destroy(Sale $sale): RedirectResponse
    {
        $sale->delete();

        return back()->with('success', 'Sale-Aktion gelöscht.');
    }
}
