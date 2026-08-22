<?php

namespace App\Http\Resources\Manage;

use App\Http\Resources\ParticipantResource;
use App\Models\Participant;
use Illuminate\Http\Request;

/**
 * @mixin Participant
 */
class RegistrationResource extends ParticipantResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            ...parent::toArray($request),
            'allowed_transitions' => $this->allowedTransitionValues(),
        ];
    }
}
