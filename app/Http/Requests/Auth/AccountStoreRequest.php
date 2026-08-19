<?php

namespace App\Http\Requests\Auth;

use App\Concerns\ProfileValidationRules;
use App\Concerns\RegistrationValidationRules;
use App\Models\Event;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class AccountStoreRequest extends FormRequest
{
    use ProfileValidationRules, RegistrationValidationRules;

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'email' => $this->emailRules(),
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
        $this->merge([
            'email' => Str::lower(trim($this->string('email')->value())),
        ]);
    }
}
