<?php

namespace App\Http\Resources;

use App\Models\Event;
use App\Models\Standing;
use App\Support\BibNumber;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Standing
 */
class StandingResource extends JsonResource
{
    public function __construct(
        Standing $standing,
        private readonly Event $race,
    ) {
        parent::__construct($standing);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'rank' => $this->rank,
            'bib_label' => BibNumber::label($this->bib_number),
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'status' => $this->runnerStatus()->value,
            'validated_laps' => $this->validated_laps,
            'covered_meters' => $this->coveredMeters($this->race->lap_distance_meters),
        ];
    }
}
