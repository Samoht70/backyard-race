<?php

namespace App\Http\Requests\Manage;

use App\Enums\LapStatus;
use App\Models\Lap;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class LapReversionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('correct', $this->lap()) === true;
    }

    public function lap(): Lap
    {
        /** @var Lap $lap */
        $lap = $this->route('lap');

        return $lap;
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
            if ($this->lap()->status === LapStatus::Validated) {
                return;
            }

            $validator->errors()->add('lap', __('race.refusal.lap_not_validated'));
        }];
    }
}
