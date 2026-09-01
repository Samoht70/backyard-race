<?php

namespace App\Actions;

use App\Enums\ExitReason;
use App\Enums\LapStatus;
use App\Exceptions\LapCorrectionRefusedException;
use App\Models\Lap;
use App\Models\Round;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class RevertLapValidation
{
    /**
     * @throws LapCorrectionRefusedException
     */
    public function __invoke(Lap $lap): LapStatus
    {
        $round = $lap->round;

        return DB::transaction(fn (): LapStatus => $this->claim($lap->id, $round));
    }

    /**
     * @throws LapCorrectionRefusedException
     */
    private function claim(int $lapId, Round $round): LapStatus
    {
        $lap = Lap::query()->whereKey($lapId)->lockForUpdate()->sole();

        if ($lap->status !== LapStatus::Validated) {
            throw LapCorrectionRefusedException::notValidated();
        }

        $status = CarbonImmutable::now()->greaterThan($round->deadline_at)
            ? LapStatus::Eliminated
            : LapStatus::Pending;

        $lap->update([
            'status' => $status,
            'validated_at' => null,
            'corrected_at' => CarbonImmutable::now(),
        ]);

        if ($status === LapStatus::Eliminated) {
            $this->takeOut($lap, $round);
        }

        return $status;
    }

    private function takeOut(Lap $lap, Round $round): void
    {
        $runner = $lap->participant;

        if (! $runner->isRunning()) {
            return;
        }

        $runner->leaveRace(ExitReason::Timeout, $round->deadline_at);
    }
}
