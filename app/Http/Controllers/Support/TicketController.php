<?php

declare(strict_types=1);

namespace App\Http\Controllers\Support;

use App\Actions\Support\OpenTicket;
use App\Actions\Support\ReplyToTicket;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Support\ReplyTicketRequest;
use App\Http\Requests\Support\StoreTicketRequest;
use App\Models\Ticket;
use App\Models\Vendor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('support.index', [
            'tickets' => $user->tickets()->with('vendor:id,name')->latest('last_reply_at')->paginate(15),
            'vendors' => Vendor::query()->approved()->orderBy('name')->get(['id', 'name']),
            'ownedPlugins' => $user->licenses()->with('plugin:id,name,vendor_id')->get()->pluck('plugin')->filter(),
            'canContactVendors' => $user->vendor === null,
        ]);
    }

    public function store(StoreTicketRequest $request, OpenTicket $openTicket): RedirectResponse
    {
        $ticket = $openTicket->handle($request->user(), $request->validated());

        return to_route('support.show', $ticket)->with('success', 'Ticket erstellt.');
    }

    public function show(Ticket $ticket): View
    {
        $this->authorize('view', $ticket);

        return view('support.show', [
            'ticket' => $ticket->load(['messages.user:id,name,role', 'vendor:id,name,user_id', 'plugin:id,name,slug', 'user:id,name']),
        ]);
    }

    public function reply(ReplyTicketRequest $request, Ticket $ticket, ReplyToTicket $reply): RedirectResponse
    {
        $this->authorize('view', $ticket);

        $reply->handle($ticket, $request->user(), $request->validated('message'));

        return back()->with('success', 'Antwort gesendet.');
    }

    public function status(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('view', $ticket);

        $reopen = $request->boolean('reopen');
        $ticket->update(['status' => $reopen ? TicketStatus::Open : TicketStatus::Closed]);

        return back()->with('success', $reopen ? 'Ticket wieder geöffnet.' : 'Ticket geschlossen.');
    }
}
