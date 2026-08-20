<?php

namespace App\Http\Resources;

use App\Models\Participant;
use App\Support\BibNumber;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Participant
 */
class ParticipantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'bib_number' => $this->bib_number,
            'bib_label' => BibNumber::label($this->bib_number),
            'first_name' => $this->user->first_name,
            'last_name' => $this->user->last_name,
            'email' => $this->user->email,
            'phone' => $this->phone,
            'birth_date' => $this->birth_date->format('Y-m-d'),
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'notes' => $this->notes,
        ];
    }
}
