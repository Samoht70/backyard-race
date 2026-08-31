<?php

namespace App\Http\Requests\Manage;

use App\Enums\LapStatus;
use App\Models\Lap;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class LapValidationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('validate', $this->lap()) === true;
    }

    public function lap(): Lap
    {
        /** @var Lap $lap */
        $lap = $this->route('lap');

        return $lap;
    }

    /**
     * @return array<string, list<string>>
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
            $lap = $this->lap();

            if ($lap->validated_at !== null) {
                return;
            }

            if ($lap->status !== LapStatus::Pending) {
                $validator->errors()->add('lap', __('race.refusal.runner_out'));

                return;
            }

            if (CarbonImmutable::now()->greaterThan($lap->round->deadline_at)) {
                $validator->errors()->add('lap', __('race.refusal.deadline_passed'));
            }
        }];
    }
}
