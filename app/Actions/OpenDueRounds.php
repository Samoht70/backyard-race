<?php

namespace App\Actions;

use App\Models\Event;
use App\Models\Round;
use App\Services\RaceSchedule\RoundSchedule;
use Carbon\CarbonImmutable;

final class OpenDueRounds
{
    /**
     * Replayable by construction: the unique key on (event_id, number) closes
     * the window, not the read that precedes it. A run that fires late still
     * stores the scheduled hours, never its own execution time.
     *
     * @return list<Round> the rounds this call opened
     */
    public function __invoke(Event $event, ?CarbonImmutable $at = null): array
    {
        if (! $event->lifecycle()->isRacing()) {
            return [];
        }

        $schedule = RoundSchedule::fromEvent($event);

        if ($schedule === null) {
            return [];
        }

        $current = $schedule->numberAt($at ?? CarbonImmutable::now());

        if ($current === null) {
            return [];
        }

        $openedThrough = (int) $event->rounds()->max('number');

        if ($openedThrough >= $current) {
            return [];
        }

        return $this->materialise($event, $schedule, $openedThrough + 1, $current);
    }

    /**
     * @return list<Round>
     */
    private function materialise(Event $event, RoundSchedule $schedule, int $from, int $through): array
    {
        return array_map(
            fn (int $number): Round => $event->rounds()->firstOrCreate(
                ['number' => $number],
                ['starts_at' => $schedule->startOf($number), 'deadline_at' => $schedule->deadlineOf($number)],
            ),
            range($from, $through),
        );
    }
}
