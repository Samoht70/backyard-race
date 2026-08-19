<?php

namespace App\Http\Requests;

use App\Concerns\RegistrationValidationRules;
use App\Models\Event;
use App\Models\Participant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RegistrationStoreRequest extends FormRequest
{
    use RegistrationValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('create', [Participant::class, $this->event()]) === true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->registrationRules();
    }

    /**
     * The refusal reaches the runner as a validation error rather than as an
     * exception, which would leave the SPA for an untranslated error page.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->event()->isFull()) {
                $validator->errors()->add('event', __('registration.refusal.full'));
            }
        }];
    }

    private function event(): Event
    {
        return Event::query()->firstOrFail();
    }
}
