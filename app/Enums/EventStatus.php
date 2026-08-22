<?php

namespace App\Enums;

/**
 * Persisted lifecycle of the single event. The transition chain is NOT declared
 * here: it lives once, in App\Services\EventLifecycle. This enum is the name of
 * the state; the state classes are its behaviour.
 */
enum EventStatus: string
{
    case Draft = 'draft';
    case Registration = 'registration';
    case Running = 'running';
    case Finished = 'finished';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('event.status.draft'),
            self::Registration => __('event.status.registration'),
            self::Running => __('event.status.running'),
            self::Finished => __('event.status.finished'),
        };
    }
}
