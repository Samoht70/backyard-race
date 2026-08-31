<?php

namespace App\Services\RaceSchedule;

use Carbon\CarbonImmutable;

final class NextRound
{
    public function __construct(
        public int $number,
        public CarbonImmutable $startsAt,
        public int $lapDurationMinutes,
    ) {}
}
