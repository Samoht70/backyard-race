<?php

namespace App\Actions;

use App\Enums\LapStatus;
use App\Exceptions\LapValidationRefusedException;
use App\Models\Lap;
use App\Models\Round;
use App\Services\RaceResult\LapPerformance;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class ValidateLap
{
    /**
     * @throws LapValidationRefusedException
     */
    public function __invoke(Lap $lap): LapPerformance
    {
        $round = $lap->round;
        $distanceMeters = $round->event->lap_distance_meters;

        return DB::transaction(fn (): LapPerformance => $this->claim($lap->id, $round, $distanceMeters));
    }

    /**
     * @throws LapValidationRefusedException
     */
    private function claim(int $lapId, Round $round, ?int $distanceMeters): LapPerformance
    {
        $lap = Lap::query()->whereKey($lapId)->lockForUpdate()->sole();
        $validatedAt = $lap->validated_at;

        if ($validatedAt !== null) {
            return LapPerformance::of($round, $validatedAt, $distanceMeters);
        }

        if ($lap->status !== LapStatus::Pending) {
            throw LapValidationRefusedException::runnerOut();
        }

        $validatedAt = CarbonImmutable::now();

        if ($validatedAt->greaterThan($round->deadline_at)) {
            throw LapValidationRefusedException::deadlinePassed();
        }

        $lap->update(['status' => LapStatus::Validated, 'validated_at' => $validatedAt]);

        return LapPerformance::of($round, $validatedAt, $distanceMeters);
    }
}
