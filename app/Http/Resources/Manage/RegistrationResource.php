<?php

namespace App\Http\Resources\Manage;

use App\Enums\RegistrationTransition;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Participant
 */
class RegistrationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'first_name' => $this->user->first_name,
            'last_name' => $this->user->last_name,
            'email' => $this->user->email,
            'phone' => $this->phone,
            'birth_date' => $this->birth_date->format('Y-m-d'),
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'notes' => $this->notes,
            'allowed_transitions' => array_map(
                fn (RegistrationTransition $transition): string => $transition->value,
                $this->lifecycle()->allowedTransitions(),
            ),
        ];
    }
}
