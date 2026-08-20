<?php

namespace App\Services\RegistrationLifecycle;

use App\Enums\RegistrationStatus;
use App\Enums\RegistrationTransition;
use App\Exceptions\RegistrationTransitionRefusedException;

final class PendingRegistrationState implements RegistrationLifecycleState
{
    public function status(): RegistrationStatus
    {
        return RegistrationStatus::Pending;
    }

    public function confirm(): RegistrationLifecycleState
    {
        return new ConfirmedRegistrationState;
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
        return [RegistrationTransition::Confirm, RegistrationTransition::Cancel];
    }

    public function isEditableByRunner(): bool
    {
        return true;
    }

    public function consumesASeat(): bool
    {
        return false;
    }
}
