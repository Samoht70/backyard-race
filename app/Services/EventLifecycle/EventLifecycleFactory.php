<?php

namespace App\Services\EventLifecycle;

use App\Enums\EventStatus;

/**
 * The only match on the status enum. It is exhaustive, so a status added
 * without its state fails static analysis rather than production.
 */
final class EventLifecycleFactory
{
    public function fromStatus(EventStatus $status): EventLifecycleState
    {
        return match ($status) {
            EventStatus::Draft => new DraftEventState,
            EventStatus::Registration => new RegistrationEventState,
            EventStatus::Running => new RunningEventState,
            EventStatus::Finished => new FinishedEventState,
        };
    }

    public function isReversible(?EventStatus $status): bool
    {
        return $status !== null && $this->fromStatus($status)->previousStatus() !== null;
    }
}
