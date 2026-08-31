<?php

namespace App\Http\Requests\Manage;

use App\Models\Participant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RunnerWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('withdraw', $this->participant()) === true;
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
            if ($this->participant()->isRunning()) {
                return;
            }

            $validator->errors()->add('runner', __('race.refusal.runner_already_out'));
        }];
    }
}
