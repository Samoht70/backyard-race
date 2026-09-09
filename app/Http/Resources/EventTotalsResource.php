<?php

namespace App\Http\Resources;

use App\Services\RaceResult\EventTotals;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin EventTotals
 */
class EventTotalsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'participants' => $this->participants,
            'validated_laps' => $this->validatedLaps,
            'covered_meters' => $this->coveredMeters,
            'duration_seconds' => $this->durationSeconds,
        ];
    }
}
