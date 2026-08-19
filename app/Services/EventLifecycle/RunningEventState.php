<?php

namespace App\Services\EventLifecycle;

use App\Enums\EventStatus;
use App\Models\Event;

final class RunningEventState implements EventLifecycleState
{
    public function status(): EventStatus
    {
        return EventStatus::Running;
    }

    public function nextStatus(): EventStatus
    {
        return EventStatus::Finished;
    }

    public function refusals(Event $event): array
    {
        return [];
    }

    public function advance(Event $event): EventLifecycleState
    {
        return new FinishedEventState;
    }

    public function allowsRegistration(): bool
    {
        return false;
    }

    public function isVisibleToParticipants(): bool
    {
        return true;
    }

    public function isEditable(): bool
    {
        return true;
    }

    public function isRacing(): bool
    {
        return true;
    }

    /**
     * BR-04 derives every lap start time from these two: changing them mid-race
     * would silently reschedule laps already run.
     */
    public function frozenAttributes(): array
    {
        return ['first_start_at', 'lap_duration_minutes'];
    }
}
