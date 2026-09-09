<?php

namespace App\Services\RaceResult;

use App\Enums\LapStatus;
use App\Models\Event;
use App\Models\Lap;

final class ResolveEventTotals
{
    public function __invoke(Event $event): EventTotals
    {
        $validatedLaps = $this->validatedLaps($event);

        return new EventTotals(
            $event->confirmedParticipantsCount(),
            $validatedLaps,
            $this->coveredMeters($event, $validatedLaps),
            $this->durationSeconds($event),
        );
    }

    private function validatedLaps(Event $event): int
    {
        return Lap::query()
            ->join('rounds', 'rounds.id', '=', 'laps.round_id')
            ->where('rounds.event_id', $event->getKey())
            ->where('laps.status', LapStatus::Validated)
            ->count();
    }

    private function coveredMeters(Event $event, int $validatedLaps): ?int
    {
        return $event->lap_distance_meters === null
            ? null
            : $validatedLaps * $event->lap_distance_meters;
    }

    private function durationSeconds(Event $event): ?int
    {
        if ($event->first_start_at === null || $event->finished_at === null) {
            return null;
        }

        return $event->finished_at->getTimestamp() - $event->first_start_at->getTimestamp();
    }
}
