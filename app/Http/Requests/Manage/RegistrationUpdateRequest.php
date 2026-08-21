<?php

namespace App\Http\Requests\Manage;

use App\Concerns\ProfileValidationRules;
use App\Concerns\RegistrationValidationRules;
use App\Models\Participant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RegistrationUpdateRequest extends FormRequest
{
    use ProfileValidationRules;
    use RegistrationValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('manage', $this->participant()) === true;
    }

    public function participant(): Participant
    {
        /** @var Participant $participant */
        $participant = $this->route('participant');

        return $participant;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->profileRules($this->participant()->user_id),
            ...$this->registrationRules(),
            'pps_number' => ['prohibited'],
        ];
    }
}
