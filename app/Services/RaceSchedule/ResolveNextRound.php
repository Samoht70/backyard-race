<?php

namespace App\Services\RaceSchedule;

use App\Models\Event;

final class ResolveNextRound
{
    public function __invoke(Event $event): ?NextRound
    {
        $schedule = RoundSchedule::fromEvent($event);

        if ($schedule === null) {
            return null;
        }

        $number = $this->lastOpened($event) + 1;

        return new NextRound($number, $schedule->startOf($number), $schedule->durationOf($number));
    }

    private function lastOpened(Event $event): int
    {
        if (! $event->exists) {
            return 0;
        }

        return (int) $event->rounds()->max('number');
    }
}
