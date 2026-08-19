<?php

namespace App\Http\Resources;

use App\Services\RaceSchedule\CurrentRound;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CurrentRound
 */
class CurrentRoundResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'number' => $this->number,
            'starts_at' => $this->startsAt->format('H:i'),
            'deadline_at' => $this->deadlineAt->format('H:i'),
        ];
    }
}
