<?php

namespace App\Http\Requests\Auth;

use App\Concerns\ProfileValidationRules;
use App\Concerns\RegistrationValidationRules;
use App\Models\Event;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AccountUpdateRequest extends FormRequest
{
    use ProfileValidationRules, RegistrationValidationRules;

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'first_name' => $this->firstNameRules(),
            'last_name' => $this->lastNameRules(),
            ...$this->registrationRules(),
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [fn (Validator $validator) => $this->refuseOutsideRegistrationWindow(
            $validator,
            Event::query()->first(),
        )];
    }

    protected function prepareForValidation(): void
    {
        $this->normalisePpsNumber();
    }
}
