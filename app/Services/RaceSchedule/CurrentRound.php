<?php

namespace App\Services\RaceSchedule;

use Carbon\CarbonImmutable;

final class CurrentRound
{
    public function __construct(
        public int $number,
        public CarbonImmutable $startsAt,
        public CarbonImmutable $deadlineAt,
    ) {}
}
