<?php

namespace App\Http\Resources;

use App\Models\Lap;
use App\Services\RaceResult\LapPerformance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Lap
 */
class RunnerLapResource extends JsonResource
{
    public function __construct(
        Lap $lap,
        private readonly ?int $lapDistanceMeters,
    ) {
        parent::__construct($lap);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $performance = $this->performance();

        return [
            'round_number' => $this->round->number,
            'corrected' => $this->corrected_at !== null,
            'duration_seconds' => $performance?->durationSeconds,
            'distance_meters' => $performance?->distanceMeters,
            'speed_kmh' => $performance?->speedKmh,
        ];
    }

    private function performance(): ?LapPerformance
    {
        return $this->validated_at === null
            ? null
            : LapPerformance::of($this->round, $this->validated_at, $this->lapDistanceMeters);
    }
}
