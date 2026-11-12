<?php

declare(strict_types=1);

namespace App\Actions\Checkout;

use App\Enums\SettlementStatus;
use App\Models\Settlement;
use App\Services\Notifier;

final class UpdateSettlementStatus
{
    public function __construct(private readonly Notifier $notifier)
    {
    }

    public function handle(Settlement $settlement, SettlementStatus $status): Settlement
    {
        $settlement->update([
            'status' => $status,
            'released_at' => $status === SettlementStatus::Available
                ? ($settlement->released_at ?? now())
                : $settlement->released_at,
            'paid_out_at' => $status === SettlementStatus::PaidOut ? now() : $settlement->paid_out_at,
        ]);

        $this->notifier->vendor(
            $settlement->vendor,
            'Abrechnung: '.$status->label(),
            'Abrechnung '.$settlement->number.' '.$status->vendorNotice().'.',
            'wallet',
            route('vendor.settlements.show', $settlement),
        );

        return $settlement;
    }
}
