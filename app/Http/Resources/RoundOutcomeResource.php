<?php

namespace App\Http\Resources;

use App\Services\RaceResult\RoundOutcome;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin RoundOutcome
 */
class RoundOutcomeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'number' => $this->number,
            'runners' => $this->runners,
            'completed_laps' => $this->completedLaps,
            'withdrawals' => $this->withdrawals,
            'timeouts' => $this->timeouts,
        ];
    }
}
