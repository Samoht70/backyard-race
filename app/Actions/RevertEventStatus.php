<?php

namespace App\Actions;

use App\Enums\EventStatus;
use App\Exceptions\EventTransitionRefusedException;
use App\Models\Event;

final class RevertEventStatus
{
    /**
     * Dropping either condition on the write lets a stale screen close an event
     * that has moved on, or turn one that just took its first registration into
     * a draft carrying a runner and an account.
     *
     * @throws EventTransitionRefusedException
     */
    public function __invoke(Event $event, EventStatus $to): Event
    {
        $from = $event->status;

        if ($event->lifecycle()->revert($event)->status() !== $to) {
            throw EventTransitionRefusedException::illegal();
        }

        $moved = Event::query()
            ->whereKey($event->getKey())
            ->where('status', $from->value)
            ->whereDoesntHave('participants')
            ->update(['status' => $to->value]);

        if ($moved === 0) {
            throw EventTransitionRefusedException::illegal();
        }

        return $event->refresh();
    }
}
