<?php

namespace App\Http\Requests;

use App\Concerns\RegistrationValidationRules;
use App\Models\Participant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RegistrationUpdateRequest extends FormRequest
{
    use RegistrationValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->participant()) === true;
    }

    public function participant(): Participant
    {
        return $this->user()->participant ?? abort(404);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->registrationRules();
    }
}
