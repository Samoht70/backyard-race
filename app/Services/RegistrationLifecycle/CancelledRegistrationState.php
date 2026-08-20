<?php

namespace App\Services\RegistrationLifecycle;

use App\Enums\RegistrationStatus;
use App\Enums\RegistrationTransition;
use App\Exceptions\RegistrationTransitionRefusedException;

final class CancelledRegistrationState implements RegistrationLifecycleState
{
    public function status(): RegistrationStatus
    {
        return RegistrationStatus::Cancelled;
    }

    public function confirm(): RegistrationLifecycleState
    {
        throw RegistrationTransitionRefusedException::illegal();
    }

    public function cancel(): RegistrationLifecycleState
    {
        throw RegistrationTransitionRefusedException::illegal();
    }

    public function reopen(): RegistrationLifecycleState
    {
        return new PendingRegistrationState;
    }

    public function allowedTransitions(): array
    {
        return [RegistrationTransition::Reopen];
    }

    public function isEditableByRunner(): bool
    {
        return false;
    }

    public function consumesASeat(): bool
    {
        return false;
    }

    public function assignsBibNumber(): bool
    {
        return false;
    }
}
