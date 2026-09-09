<?php

namespace App\Services\RaceBoard;

use App\Models\Participant;
use App\Models\Round;
use Illuminate\Database\Eloquent\Collection;

final class RoundBoard
{
    /**
     * @param  Collection<int, Participant>  $runners
     */
    public function __construct(
        public Round $round,
        public Collection $runners,
        public RunnerTally $tally,
    ) {}
}
