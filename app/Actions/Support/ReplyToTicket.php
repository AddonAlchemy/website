<?php

declare(strict_types=1);

namespace App\Actions\Support;

use App\Enums\TicketStatus;
use App\Exceptions\MarketplaceException;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Notifier;
use Illuminate\Support\Facades\DB;

final class ReplyToTicket
{
    public function __construct(private readonly Notifier $notifier)
    {
    }

    /**
     * @throws MarketplaceException
     */
    public function handle(Ticket $ticket, User $author, string $body): void
    {
        if ($ticket->isClosed()) {
            throw new MarketplaceException('Dieses Ticket ist geschlossen.');
        }

        $fromCreator = $ticket->user_id === $author->id;

        DB::transaction(static function () use ($ticket, $author, $body, $fromCreator): void {
            $ticket->messages()->create(['user_id' => $author->id, 'body' => $body]);
            $ticket->update([
                'status' => $fromCreator ? TicketStatus::Open : TicketStatus::Answered,
                'last_reply_at' => now(),
            ]);
        });

        $url = route('support.show', $ticket);

        match (true) {
            ! $fromCreator => $this->notifier->user($ticket->user, 'Antwort auf dein Ticket', $ticket->subject, 'message-square', $url),
            $ticket->vendor !== null => $this->notifier->vendor($ticket->vendor, 'Antwort im Ticket', $ticket->subject, 'message-square', $url),
            default => $this->notifier->admins('Antwort im Ticket', $ticket->subject, 'message-square', $url),
        };
    }
}
