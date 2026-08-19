<?php

namespace App\Concerns;

use App\Models\Event;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Validator;

trait RegistrationValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function registrationRules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:40'],
            'birth_date' => ['required', 'date', 'before_or_equal:-18 years'],
            'emergency_contact_name' => ['required', 'string', 'max:120'],
            'emergency_contact_phone' => ['required', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function refuseOutsideRegistrationWindow(Validator $validator, ?Event $event): void
    {
        if ($event === null || ! $event->lifecycle()->allowsRegistration()) {
            $validator->errors()->add('event', __('registration.refusal.closed'));

            return;
        }

        if ($event->isFull()) {
            $validator->errors()->add('event', __('registration.refusal.full'));
        }
    }
}
