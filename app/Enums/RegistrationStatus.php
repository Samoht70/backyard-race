<?php

namespace App\Enums;

/**
 * The transition chain is NOT declared here: it lives once, in
 * App\Services\RegistrationLifecycle. This enum is the name of the state.
 */
enum RegistrationStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('registration.status.pending'),
            self::Confirmed => __('registration.status.confirmed'),
            self::Cancelled => __('registration.status.cancelled'),
        };
    }
}
