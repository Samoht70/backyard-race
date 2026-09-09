<?php

namespace App\Actions;

use App\Enums\LapStatus;
use App\Exceptions\LapCorrectionRefusedException;
use App\Models\Lap;
use App\Models\Round;
use App\Services\RaceResult\LapPerformance;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class ReinstateRunner
{
    /**
     * @throws LapCorrectionRefusedException
     */
    public function __invoke(Lap $lap, CarbonImmutable $finishedAt): LapPerformance
    {
        $round = $lap->round;
        $distanceMeters = $round->event->lap_distance_meters;

        return DB::transaction(
            fn (): LapPerformance => $this->claim($lap->id, $round, $finishedAt, $distanceMeters),
        );
    }

    /**
     * @throws LapCorrectionRefusedException
     */
    private function claim(int $lapId, Round $round, CarbonImmutable $finishedAt, ?int $distanceMeters): LapPerformance
    {
        $lap = Lap::query()->whereKey($lapId)->lockForUpdate()->sole();

        if ($lap->status === LapStatus::Validated) {
            throw LapCorrectionRefusedException::alreadyValidated();
        }

        if ($finishedAt->lessThan($round->starts_at)) {
            throw LapCorrectionRefusedException::beforeTheStart();
        }

        $lap->update([
            'status' => LapStatus::Validated,
            'validated_at' => $finishedAt,
            'corrected_at' => CarbonImmutable::now(),
        ]);

        $lap->participant->returnToRace();

        return LapPerformance::of($round, $finishedAt, $distanceMeters);
    }
}
