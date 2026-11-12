<?php

declare(strict_types=1);

namespace App\Actions\Support;

use App\Models\Ticket;
use App\Models\User;
use App\Models\Vendor;
use App\Services\KeyGenerator;
use App\Services\Notifier;
use Illuminate\Support\Facades\DB;

final class OpenTicket
{
    public function __construct(
        private readonly KeyGenerator $keys,
        private readonly Notifier $notifier,
    ) {
    }

    /**
     * @param  array{subject: string, message: string, vendor_id?: ?int, plugin_id?: ?int}  $data
     */
    public function handle(User $user, array $data): Ticket
    {
        $vendor = isset($data['vendor_id']) ? Vendor::query()->with('user')->find($data['vendor_id']) : null;

        $ticket = DB::transaction(function () use ($user, $data, $vendor): Ticket {
            $ticket = $user->tickets()->create([
                'number' => $this->keys->ticketNumber(),
                'vendor_id' => $vendor?->id,
                'plugin_id' => $data['plugin_id'] ?? null,
                'subject' => $data['subject'],
                'last_reply_at' => now(),
            ]);

            $ticket->messages()->create(['user_id' => $user->id, 'body' => $data['message']]);

            return $ticket;
        });

        $url = route('support.show', $ticket);

        $vendor !== null
            ? $this->notifier->vendor($vendor, 'Neues Support-Ticket', $ticket->subject, 'life-buoy', $url)
            : $this->notifier->admins('Neues Support-Ticket', $ticket->subject, 'life-buoy', $url);

        return $ticket;
    }
}
