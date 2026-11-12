<?php

declare(strict_types=1);

namespace App\Actions\Vendors;

use App\Enums\VendorStatus;
use App\Models\Vendor;
use App\Services\Notifier;

final class UpdateVendorStatus
{
    public function __construct(private readonly Notifier $notifier)
    {
    }

    public function handle(Vendor $vendor, VendorStatus $status, ?string $reason = null): Vendor
    {
        $vendor->update([
            'status' => $status,
            'rejection_reason' => $status === VendorStatus::Approved ? null : $reason,
            'approved_at' => $status === VendorStatus::Approved ? ($vendor->approved_at ?? now()) : $vendor->approved_at,
        ]);

        $message = match ($status) {
            VendorStatus::Approved => 'Dein Studio wurde freigeschaltet. Du kannst jetzt Plugins einreichen.',
            VendorStatus::Rejected => 'Deine Vendor-Bewerbung wurde abgelehnt.'.($reason ? ' Grund: '.$reason : ''),
            VendorStatus::Suspended => 'Dein Studio wurde vorübergehend gesperrt.'.($reason ? ' Grund: '.$reason : ''),
            VendorStatus::Pending => 'Deine Vendor-Bewerbung wird erneut geprüft.',
        };

        $this->notifier->vendor($vendor, 'Vendor-Status: '.$status->label(), $message, 'store', route('vendor.dashboard'));

        return $vendor;
    }
}
