<?php

namespace App\Services\RaceResult;

final class RoundOutcome
{
    public function __construct(
        public int $number,
        public int $runners,
        public int $completedLaps,
        public int $withdrawals,
        public int $timeouts,
    ) {}
}
