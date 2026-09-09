<?php

namespace App\Services\RaceResult;

final class EventTotals
{
    public function __construct(
        public int $participants,
        public int $validatedLaps,
        public ?int $coveredMeters,
        public ?int $durationSeconds,
    ) {}
}
