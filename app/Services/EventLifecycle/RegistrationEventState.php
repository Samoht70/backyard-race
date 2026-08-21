<?php

namespace App\Services\EventLifecycle;

use App\Enums\EventStatus;
use App\Exceptions\EventTransitionRefusedException;
use App\Models\Event;

final class RegistrationEventState implements EventLifecycleState
{
    public function status(): EventStatus
    {
        return EventStatus::Registration;
    }

    public function nextStatus(): EventStatus
    {
        return EventStatus::Running;
    }

    public function refusals(Event $event): array
    {
        $missing = [];

        if ($event->first_start_at === null) {
            $missing[] = __('event.refusal.missing_first_start');
        }

        if ($event->lap_distance_meters === null) {
            $missing[] = __('event.refusal.missing_lap_distance');
        }

        if ($event->lap_duration_minutes === null) {
            $missing[] = __('event.refusal.missing_lap_duration');
        }

        return $missing;
    }

    public function advance(Event $event): EventLifecycleState
    {
        if ($this->refusals($event) !== []) {
            throw EventTransitionRefusedException::incomplete();
        }

        return new RunningEventState;
    }

    public function allowsRegistration(): bool
    {
        return true;
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
        return false;
    }

    public function frozenAttributes(): array
    {
        return [];
    }
}
