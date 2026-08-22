<?php

namespace App\Console\Commands;

use App\Actions\OpenDueRounds;
use App\Models\Event;
use Illuminate\Console\Command;

class OpenDueRoundsCommand extends Command
{
    protected $signature = 'race:open-rounds';

    protected $description = 'Open every round of the race due at the current server time';

    public function handle(OpenDueRounds $openDueRounds): int
    {
        $this->info(count($openDueRounds(Event::currentOrNew())).' round(s) opened.');

        return self::SUCCESS;
    }
}
