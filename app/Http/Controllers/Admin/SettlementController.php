<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Checkout\UpdateSettlementStatus;
use App\Enums\SettlementStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettlementRequest;
use App\Models\Settlement;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class SettlementController extends Controller
{
    public function index(Request $request): View
    {
        $status = SettlementStatus::tryFrom((string) $request->query('status'));

        return view('admin.settlements', [
            'settlements' => Settlement::query()
                ->with(['vendor:id,name', 'order:id,number'])
                ->when($status, static fn ($query) => $query->where('status', $status))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'totals' => Settlement::query()->toBase()
                ->selectRaw('status, COALESCE(SUM(net_cents), 0) as net, COALESCE(SUM(platform_fee_cents), 0) as fees')
                ->groupBy('status')
                ->get()
                ->keyBy('status'),
            'statuses' => SettlementStatus::cases(),
            'status' => $status,
        ]);
    }

    public function update(UpdateSettlementRequest $request, Settlement $settlement, UpdateSettlementStatus $update): RedirectResponse
    {
        $update->handle($settlement, SettlementStatus::from($request->validated('status')));

        return back()->with('success', 'Status aktualisiert.');
    }
}
