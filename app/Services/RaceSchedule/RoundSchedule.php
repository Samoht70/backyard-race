<?php

namespace App\Services\RaceSchedule;

use App\Models\Event;
use App\Models\ScheduleSegment;
use Carbon\CarbonImmutable;

final class RoundSchedule
{
    /**
     * @param  array<int, int>  $segments  lap duration in minutes, keyed by the round it takes effect on
     */
    public function __construct(
        private CarbonImmutable $firstStartAt,
        private int $lapDurationMinutes,
        private array $segments = [],
    ) {
        ksort($this->segments);
    }

    public static function fromEvent(Event $event): ?self
    {
        $firstStartAt = $event->first_start_at;
        $lapDurationMinutes = $event->lap_duration_minutes;

        if ($firstStartAt === null || $lapDurationMinutes === null || $lapDurationMinutes < 1) {
            return null;
        }

        return new self($firstStartAt, $lapDurationMinutes, self::segmentsOf($event));
    }

    public function startOf(int $number): CarbonImmutable
    {
        return $this->firstStartAt->addMinutes($this->minutesBefore($number));
    }

    public function deadlineOf(int $number): CarbonImmutable
    {
        return $this->startOf($number + 1);
    }

    public function numberAt(CarbonImmutable $at): ?int
    {
        $elapsed = $at->getTimestamp() - $this->firstStartAt->getTimestamp();

        if ($elapsed < 0) {
            return null;
        }

        return $this->numberAfter(intdiv($elapsed, 60));
    }

    public function durationOf(int $number): int
    {
        $duration = $this->lapDurationMinutes;

        foreach ($this->segments as $from => $segmentDuration) {
            if ($from > $number) {
                break;
            }

            $duration = $segmentDuration;
        }

        return $duration;
    }

    private function minutesBefore(int $number): int
    {
        $minutes = 0;
        $round = 1;
        $duration = $this->lapDurationMinutes;

        foreach ($this->segments as $from => $segmentDuration) {
            if ($from >= $number) {
                break;
            }

            $minutes += max($from - $round, 0) * $duration;
            $round = max($from, $round);
            $duration = $segmentDuration;
        }

        return $minutes + ($number - $round) * $duration;
    }

    private function numberAfter(int $minutes): int
    {
        $number = 1;
        $duration = $this->lapDurationMinutes;

        foreach ($this->segments as $from => $segmentDuration) {
            $span = max($from - $number, 0) * $duration;

            if ($minutes < $span) {
                break;
            }

            $minutes -= $span;
            $number = max($from, $number);
            $duration = $segmentDuration;
        }

        return $number + intdiv($minutes, $duration);
    }

    /**
     * @return array<int, int>
     */
    private static function segmentsOf(Event $event): array
    {
        if (! $event->exists) {
            return [];
        }

        return $event->scheduleSegments
            ->mapWithKeys(fn (ScheduleSegment $segment): array => [
                $segment->from_round_number => $segment->lap_duration_minutes,
            ])
            ->all();
    }
}
