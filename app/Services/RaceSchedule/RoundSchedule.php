<?php

namespace App\Services\RaceSchedule;

use App\Models\Event;
use Carbon\CarbonImmutable;

/**
 * The time reference of the whole race, derived from the two attributes the
 * event freezes once running. It knows nothing of the event status: an event
 * still taking registrations has a schedule, it simply has no current round.
 */
final class RoundSchedule
{
    public function __construct(
        private CarbonImmutable $firstStartAt,
        private int $lapDurationMinutes,
    ) {}

    /**
     * A lap under a minute would make numberAt() divide by zero.
     */
    public static function fromEvent(Event $event): ?self
    {
        $firstStartAt = $event->first_start_at;
        $lapDurationMinutes = $event->lap_duration_minutes;

        if ($firstStartAt === null || $lapDurationMinutes === null || $lapDurationMinutes < 1) {
            return null;
        }

        return new self($firstStartAt, $lapDurationMinutes);
    }

    public function startOf(int $number): CarbonImmutable
    {
        return $this->firstStartAt->addMinutes($this->lapDurationMinutes * ($number - 1));
    }

    public function deadlineOf(int $number): CarbonImmutable
    {
        return $this->startOf($number + 1);
    }

    /**
     * The window of round N is [startOf(N), startOf(N + 1)): on the deadline
     * second the next round is already the current one.
     */
    public function numberAt(CarbonImmutable $at): ?int
    {
        $elapsed = $at->getTimestamp() - $this->firstStartAt->getTimestamp();

        if ($elapsed < 0) {
            return null;
        }

        return intdiv($elapsed, $this->lapDurationMinutes * 60) + 1;
    }
}
