<?php

namespace App\Console\Commands;

use App\Actions\EliminateOverdueRunners;
use App\Actions\OpenDueRounds;
use App\Models\Event;
use Illuminate\Console\Command;

class AdvanceRaceCommand extends Command
{
    protected $signature = 'race:advance';

    protected $description = 'Eliminate the runners whose deadline passed, then open every round now due';

    public function handle(EliminateOverdueRunners $eliminateOverdueRunners, OpenDueRounds $openDueRounds): int
    {
        $event = Event::currentOrNew();

        $this->info($eliminateOverdueRunners($event).' runner(s) eliminated.');
        $this->info(count($openDueRounds($event)).' round(s) opened.');

        $this->comment('Race advanced.');

        return self::SUCCESS;
    }
}
