<?php

namespace App\Enums;

use App\Services\RegistrationLifecycle\RegistrationLifecycleState;

enum RegistrationOutcome: string
{
    case Approved = 'approved';
    case Refused = 'refused';
    case Cancelled = 'cancelled';
    case Reopened = 'reopened';

    public static function of(RegistrationLifecycleState $leaving, RegistrationTransition $transition): self
    {
        return match ($transition) {
            RegistrationTransition::Confirm => self::Approved,
            RegistrationTransition::Cancel => $leaving->consumesASeat()
                ? self::Cancelled
                : self::Refused,
            RegistrationTransition::Reopen => self::Reopened,
        };
    }

    public function mailKey(): string
    {
        return match ($this) {
            self::Approved => 'mail.registration_approved',
            self::Refused => 'mail.registration_refused',
            self::Cancelled => 'mail.registration_cancelled',
            self::Reopened => 'mail.registration_reopened',
        };
    }
}
