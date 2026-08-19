<?php

namespace App\Services\RaceSchedule;

use App\Models\Event;
use Carbon\CarbonImmutable;

final class ResolveCurrentRound
{
    public function __invoke(Event $event, ?CarbonImmutable $at = null): ?CurrentRound
    {
        if (! $event->lifecycle()->isRacing()) {
            return null;
        }

        $schedule = RoundSchedule::fromEvent($event);

        if ($schedule === null) {
            return null;
        }

        $number = $schedule->numberAt($at ?? CarbonImmutable::now());

        if ($number === null) {
            return null;
        }

        return new CurrentRound($number, $schedule->startOf($number), $schedule->deadlineOf($number));
    }
}
