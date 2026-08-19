<?php

namespace App\Services\RaceSchedule;

use App\Models\Event;
use Carbon\CarbonImmutable;

final class RoundSchedule
{
    public function __construct(
        private CarbonImmutable $firstStartAt,
        private int $lapDurationMinutes,
    ) {}

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

    public function numberAt(CarbonImmutable $at): ?int
    {
        $elapsed = $at->getTimestamp() - $this->firstStartAt->getTimestamp();

        if ($elapsed < 0) {
            return null;
        }

        return intdiv($elapsed, $this->lapDurationMinutes * 60) + 1;
    }
}
