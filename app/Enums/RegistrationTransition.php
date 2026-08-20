<?php

namespace App\Enums;

use App\Exceptions\RegistrationTransitionRefusedException;
use App\Services\RegistrationLifecycle\RegistrationLifecycleState;

enum RegistrationTransition: string
{
    case Confirm = 'confirm';
    case Cancel = 'cancel';
    case Reopen = 'reopen';

    /**
     * @throws RegistrationTransitionRefusedException
     */
    public function apply(RegistrationLifecycleState $state): RegistrationLifecycleState
    {
        return match ($this) {
            self::Confirm => $state->confirm(),
            self::Cancel => $state->cancel(),
            self::Reopen => $state->reopen(),
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Confirm => __('registration.transition.confirm'),
            self::Cancel => __('registration.transition.cancel'),
            self::Reopen => __('registration.transition.reopen'),
        };
    }
}
