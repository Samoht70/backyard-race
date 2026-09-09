<?php

namespace App\Services\RaceBoard;

final class RunnerTally
{
    public function __construct(
        public int $running,
        public int $out,
    ) {}
}
