<?php

declare(strict_types=1);

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class TicketController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('vendor.tickets', [
            'tickets' => $request->user()->vendor->tickets()
                ->with(['user:id,name,email', 'plugin:id,name'])
                ->latest('last_reply_at')
                ->paginate(20),
        ]);
    }
}
