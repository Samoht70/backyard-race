<?php

namespace App\Services\RaceSchedule;

use Carbon\CarbonImmutable;

/**
 * Where the server clock stands, computed and never persisted. Deliberately
 * not the Round model: saving one would open a round behind the elimination
 * BR-11 owes the runners of the previous one, so it must be inexpressible.
 */
final class CurrentRound
{
    public function __construct(
        public int $number,
        public CarbonImmutable $startsAt,
        public CarbonImmutable $deadlineAt,
    ) {}
}
