<?php

namespace App\Services\RegistrationLifecycle;

use App\Enums\RegistrationStatus;
use App\Enums\RegistrationTransition;
use App\Exceptions\RegistrationTransitionRefusedException;

final class ConfirmedRegistrationState implements RegistrationLifecycleState
{
    public function status(): RegistrationStatus
    {
        return RegistrationStatus::Confirmed;
    }

    public function confirm(): RegistrationLifecycleState
    {
        throw RegistrationTransitionRefusedException::illegal();
    }

    public function cancel(): RegistrationLifecycleState
    {
        return new CancelledRegistrationState;
    }

    public function reopen(): RegistrationLifecycleState
    {
        throw RegistrationTransitionRefusedException::illegal();
    }

    public function allowedTransitions(): array
    {
        return [RegistrationTransition::Cancel];
    }

    public function isEditableByRunner(): bool
    {
        return false;
    }

    public function consumesASeat(): bool
    {
        return true;
    }
}
