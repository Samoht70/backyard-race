<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;

trait PasswordValidationRules
{
    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function currentPasswordRules(): array
    {
        return ['required', 'string', 'current_password'];
    }
}
