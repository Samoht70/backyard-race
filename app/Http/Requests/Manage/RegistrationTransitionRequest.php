<?php

namespace App\Http\Requests\Manage;

use App\Enums\RegistrationTransition;
use App\Models\Participant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RegistrationTransitionRequest extends FormRequest
{
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
        return ['transition' => ['required', Rule::enum(RegistrationTransition::class)]];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $participant = $this->participant();
            $requested = $this->string('transition')->value();

            if (! in_array($requested, $this->allowedTransitions($participant), true)) {
                $validator->errors()->add('transition', __('registration.refusal.illegal_transition'));

                return;
            }

            if ($requested === RegistrationTransition::Confirm->value && $participant->event->isFull()) {
                $validator->errors()->add('transition', __('registration.refusal.full'));
            }
        }];
    }

    /**
     * @return list<string>
     */
    private function allowedTransitions(Participant $participant): array
    {
        return array_map(
            fn (RegistrationTransition $transition): string => $transition->value,
            $participant->lifecycle()->allowedTransitions(),
        );
    }
}
