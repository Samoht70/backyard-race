<?php

namespace App\Services\RegistrationLifecycle;

use App\Enums\RegistrationStatus;
use App\Enums\RegistrationTransition;
use App\Exceptions\RegistrationTransitionRefusedException;

interface RegistrationLifecycleState
{
    public function status(): RegistrationStatus;

    /**
     * @throws RegistrationTransitionRefusedException
     */
    public function confirm(): RegistrationLifecycleState;

    /**
     * @throws RegistrationTransitionRefusedException
     */
    public function cancel(): RegistrationLifecycleState;

    /**
     * @throws RegistrationTransitionRefusedException
     */
    public function reopen(): RegistrationLifecycleState;

    /**
     * @return list<RegistrationTransition>
     */
    public function allowedTransitions(): array;

    public function isEditableByRunner(): bool;

    public function consumesASeat(): bool;
}
