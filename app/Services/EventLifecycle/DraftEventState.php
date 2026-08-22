<?php

namespace App\Services\EventLifecycle;

use App\Enums\EventStatus;
use App\Exceptions\EventTransitionRefusedException;
use App\Models\Event;

final class DraftEventState implements EventLifecycleState
{
    public function status(): EventStatus
    {
        return EventStatus::Draft;
    }

    public function nextStatus(): EventStatus
    {
        return EventStatus::Registration;
    }

    public function previousStatus(): ?EventStatus
    {
        return null;
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
        return new RegistrationEventState;
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
        return false;
    }

    public function isEditable(): bool
    {
        return true;
    }

    public function isRacing(): bool
    {
        return false;
    }

    public function frozenAttributes(): array
    {
        return [];
    }
}
