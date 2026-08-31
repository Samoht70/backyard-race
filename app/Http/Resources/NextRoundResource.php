<?php

namespace App\Http\Resources;

use App\Services\RaceSchedule\NextRound;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin NextRound
 */
class NextRoundResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'number' => $this->number,
            'starts_at' => $this->startsAt->format('H:i'),
            'lap_duration_minutes' => $this->lapDurationMinutes,
        ];
    }
}
