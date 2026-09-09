<?php

namespace App\Services\RaceBoard;

use App\Enums\RegistrationStatus;
use App\Models\Event;

final class ResolveRunnerTally
{
    public function __invoke(Event $event): RunnerTally
    {
        $counts = (array) $event->participants()
            ->where('status', RegistrationStatus::Confirmed)
            ->toBase()
            ->selectRaw('sum(case when exited_at is null then 1 else 0 end) as still_running')
            ->selectRaw('sum(case when exited_at is null then 0 else 1 end) as gone')
            ->first();

        return new RunnerTally(
            (int) ($counts['still_running'] ?? 0),
            (int) ($counts['gone'] ?? 0),
        );
    }
}
