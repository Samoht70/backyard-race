<?php

namespace App\Http\Resources\Manage;

use App\Models\Lap;
use App\Support\BibNumber;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Lap
 */
class CorrectableLapResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $runner = $this->participant;
        $round = $this->round;

        return [
            'lap_id' => $this->id,
            'lap_status' => $this->status->value,
            'corrected' => $this->corrected_at !== null,
            'validated_at' => $this->validated_at?->format('H:i'),
            'round_number' => $round->number,
            'round_starts_at' => $round->starts_at->format('H:i'),
            'round_deadline_at' => $round->deadline_at->format('H:i'),
            'runner_id' => $runner->id,
            'bib_label' => BibNumber::label($runner->bib_number),
            'first_name' => $runner->user->first_name,
            'last_name' => $runner->user->last_name,
            'status' => $runner->runnerStatus()->value,
            'validated_laps' => $runner->validatedLapsCount(),
        ];
    }
}
