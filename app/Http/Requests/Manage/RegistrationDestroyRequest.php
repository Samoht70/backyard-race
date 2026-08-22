<?php

namespace App\Http\Requests\Manage;

use App\Models\Participant;
use App\Support\RegistrationDeletion;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RegistrationDestroyRequest extends FormRequest
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
        return [];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $refusal = RegistrationDeletion::refusal($this->participant()->event);

            if ($refusal !== null) {
                $validator->errors()->add('registration', $refusal);
            }
        }];
    }
}
