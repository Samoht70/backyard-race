<?php

namespace App\Http\Requests\Manage;

use App\Enums\RegistrationStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistrationIndexRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['status' => ['nullable', Rule::enum(RegistrationStatus::class)]];
    }

    /**
     * A trafficked query string reaches this on a plain navigation, where a 422
     * would leave the manager on an untranslated error page instead of the list.
     */
    protected function prepareForValidation(): void
    {
        if ($this->enum('status', RegistrationStatus::class) === null) {
            $this->request->remove('status');
        }
    }

    public function status(): ?RegistrationStatus
    {
        return $this->enum('status', RegistrationStatus::class);
    }
}
