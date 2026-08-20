<?php

namespace App\Services\RegistrationLifecycle;

use App\Enums\RegistrationStatus;

final class RegistrationLifecycleFactory
{
    public function fromStatus(RegistrationStatus $status): RegistrationLifecycleState
    {
        return match ($status) {
            RegistrationStatus::Pending => new PendingRegistrationState,
            RegistrationStatus::Confirmed => new ConfirmedRegistrationState,
            RegistrationStatus::Cancelled => new CancelledRegistrationState,
        };
    }
}
