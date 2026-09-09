<?php

namespace App\Actions;

use App\Enums\ExitReason;
use App\Exceptions\RunnerExitRefusedException;
use App\Models\Participant;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class WithdrawRunner
{
    /**
     * @throws RunnerExitRefusedException
     */
    public function __invoke(Participant $runner): void
    {
        DB::transaction(function () use ($runner): void {
            $this->claim($runner->getKey());
        });
    }

    /**
     * @throws RunnerExitRefusedException
     */
    private function claim(int $runnerId): void
    {
        $runner = Participant::query()->whereKey($runnerId)->lockForUpdate()->sole();

        if (! $runner->isRunning()) {
            throw RunnerExitRefusedException::alreadyOut();
        }

        $runner->leaveRace(ExitReason::Withdrawal, CarbonImmutable::now());
    }
}
