<?php

namespace App\Http\Resources;

use App\Models\Lap;
use App\Models\Participant;
use App\Models\Round;
use App\Services\RaceResult\LapPerformance;
use App\Support\BibNumber;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Participant
 */
class RoundRunnerResource extends JsonResource
{
    public function __construct(
        Participant $runner,
        private readonly Round $round,
        private readonly ?int $lapDistanceMeters,
    ) {
        parent::__construct($runner);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $lap = $this->lap();
        $performance = $this->performanceOf($lap);

        return [
            'runner_id' => $this->id,
            'lap_id' => $lap->id,
            'lap_status' => $lap->status->value,
            'bib_label' => BibNumber::label($this->bib_number),
            'first_name' => $this->user->first_name,
            'last_name' => $this->user->last_name,
            'status' => $this->runnerStatus()->value,
            'validated_laps' => $this->validatedLapsCount(),
            'covered_meters' => $this->coveredMeters(),
            'validated_at' => $performance?->validatedAt->format('H:i:s'),
            'duration_seconds' => $performance?->durationSeconds,
            'distance_meters' => $performance?->distanceMeters,
            'speed_kmh' => $performance?->speedKmh,
        ];
    }

    private function coveredMeters(): ?int
    {
        return $this->lapDistanceMeters === null
            ? null
            : $this->validatedLapsCount() * $this->lapDistanceMeters;
    }

    private function lap(): Lap
    {
        return $this->laps->sole();
    }

    private function performanceOf(Lap $lap): ?LapPerformance
    {
        $validatedAt = $lap->validated_at;

        return $validatedAt === null
            ? null
            : LapPerformance::of($this->round, $validatedAt, $this->lapDistanceMeters);
    }
}
