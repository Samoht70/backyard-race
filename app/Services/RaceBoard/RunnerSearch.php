<?php

namespace App\Services\RaceBoard;

use App\Models\Participant;
use Illuminate\Database\Eloquent\Collection;

final class RunnerSearch
{
    /**
     * @param  Collection<int, Participant>  $runners
     */
    public function __construct(
        public Collection $runners,
        public RunnerTally $tally,
    ) {}
}
