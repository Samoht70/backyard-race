<?php

namespace App\Services\EventLifecycle;

use App\Enums\EventStatus;
use App\Enums\Permission;
use App\Exceptions\EventTransitionRefusedException;
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

    public function previousStatus(): ?EventStatus
    {
        return null;
    }

    public function advancePermission(): Permission
    {
        return Permission::FinishEvent;
    }

    public function refusals(Event $event): array
    {
        return [];
    }

    public function revertRefusals(Event $event): array
    {
        return [];
    }

    public function advance(Event $event): EventLifecycleState
    {
        return new FinishedEventState;
    }

    public function revert(Event $event): EventLifecycleState
    {
        throw EventTransitionRefusedException::illegal();
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
     * Changing either mid-race silently reschedules rounds already run.
     */
    public function frozenAttributes(): array
    {
        return ['first_start_at', 'lap_duration_minutes'];
    }
}
