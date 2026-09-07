<?php

namespace App\Http\Resources;

use App\Models\Participant;
use App\Support\BibNumber;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Participant
 */
class RunnerSearchResultResource extends JsonResource
{
    public function __construct(
        Participant $runner,
        private readonly ?int $lapDistanceMeters,
    ) {
        parent::__construct($runner);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'runner_id' => $this->id,
            'bib_label' => BibNumber::label($this->bib_number),
            'first_name' => $this->user->first_name,
            'last_name' => $this->user->last_name,
            'status' => $this->runnerStatus()->value,
            'validated_laps' => $this->validatedLapsCount(),
            'covered_meters' => $this->coveredMeters($this->lapDistanceMeters),
            'last_validated_round' => $this->lastValidatedRoundNumber(),
            'exited_at' => $this->exited_at?->format('H:i'),
        ];
    }
}
