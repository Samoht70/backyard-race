<?php

namespace App\Http\Requests\Profile;

use App\Concerns\PasswordValidationRules;
use App\Support\AccessCode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileDeleteRequest extends FormRequest
{
    use PasswordValidationRules;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'password' => $this->currentPasswordRules(),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'password' => AccessCode::normalise($this->string('password')->value()),
        ]);
    }
}
