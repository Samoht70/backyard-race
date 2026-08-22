<?php

namespace App\Services\EventLifecycle;

use App\Enums\EventStatus;
use App\Exceptions\EventTransitionRefusedException;
use App\Models\Event;

final class FinishedEventState implements EventLifecycleState
{
    public function status(): EventStatus
    {
        return EventStatus::Finished;
    }

    public function nextStatus(): ?EventStatus
    {
        return null;
    }

    public function previousStatus(): ?EventStatus
    {
        return null;
    }

    public function refusals(Event $event): array
    {
        return [__('event.refusal.finished')];
    }

    public function revertRefusals(Event $event): array
    {
        return [__('event.refusal.finished')];
    }

    public function advance(Event $event): EventLifecycleState
    {
        throw EventTransitionRefusedException::terminal();
    }

    public function revert(Event $event): EventLifecycleState
    {
        throw EventTransitionRefusedException::terminal();
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
        return false;
    }

    public function isRacing(): bool
    {
        return false;
    }

    public function frozenAttributes(): array
    {
        return [
            'name',
            'description',
            'first_start_at',
            'lap_distance_meters',
            'lap_duration_minutes',
            'address',
            'latitude',
            'longitude',
            'max_participants',
        ];
    }
}
