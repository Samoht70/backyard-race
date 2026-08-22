<?php

namespace App\Http\Resources;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Event
 */
class BoardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'status' => $this->status->value,
            'confirmed_participants' => $this->confirmedParticipantsCount(),
            'max_participants' => $this->max_participants,
            'first_start_time' => $this->first_start_at?->format('H:i'),
            'first_start_day' => $this->first_start_at?->translatedFormat('D j M'),
        ];
    }
}
