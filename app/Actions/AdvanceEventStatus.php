<?php

namespace App\Actions;

use App\Enums\EventStatus;
use App\Exceptions\EventTransitionRefusedException;
use App\Models\Event;

final class AdvanceEventStatus
{
    /**
     * The update is conditional on the status the caller believed it was
     * leaving. Between the form request's check and this write, a concurrent
     * request can have moved the event on; without the condition the manager's
     * approved `registration` would land on an event already running, and no
     * transition can be undone.
     *
     * @throws EventTransitionRefusedException
     */
    public function __invoke(Event $event, EventStatus $to): Event
    {
        $from = $event->status;

        if ($event->lifecycle()->advance($event)->status() !== $to) {
            throw EventTransitionRefusedException::illegal();
        }

        $moved = Event::query()
            ->whereKey($event->getKey())
            ->where('status', $from->value)
            ->update(['status' => $to->value]);

        if ($moved === 0) {
            throw EventTransitionRefusedException::illegal();
        }

        return $event->refresh();
    }
}
