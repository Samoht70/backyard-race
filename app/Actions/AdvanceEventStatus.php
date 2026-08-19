<?php

namespace App\Actions;

use App\Exceptions\EventTransitionRefusedException;
use App\Models\Event;

final class AdvanceEventStatus
{
    /**
     * @throws EventTransitionRefusedException
     */
    public function __invoke(Event $event): Event
    {
        $event->status = $event->lifecycle()->advance($event)->status();
        $event->save();

        return $event;
    }
}
