<?php

namespace App\Http\Resources;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The screens receive the first start as the two controls they render, not as
 * the single instant the schema stores.
 *
 * @mixin Event
 */
class EventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status->value,
            'start_date' => $this->first_start_at?->format('Y-m-d'),
            'start_time' => $this->first_start_at?->format('H:i'),
            'lap_distance_meters' => $this->lap_distance_meters,
            'lap_duration_minutes' => $this->lap_duration_minutes,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'max_participants' => $this->max_participants,
            'confirmed_participants' => $this->confirmedParticipantsCount(),
        ];
    }
}
