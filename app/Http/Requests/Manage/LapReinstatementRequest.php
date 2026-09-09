<?php

namespace App\Http\Requests\Manage;

use App\Enums\LapStatus;
use App\Models\Lap;
use App\Services\RaceCorrection\CorrectionTime;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class LapReinstatementRequest extends FormRequest
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

    public function finishedAt(): CarbonImmutable
    {
        return CorrectionTime::on($this->lap()->round, $this->string('finished_at')->toString());
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'finished_at' => ['required', 'date_format:H:i,H:i:s'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $lap = $this->lap();

            if ($lap->status === LapStatus::Validated) {
                $validator->errors()->add('lap', __('race.refusal.lap_already_validated'));

                return;
            }

            if ($validator->errors()->hasAny('finished_at')) {
                return;
            }

            if ($this->finishedAt()->lessThan($lap->round->starts_at)) {
                $validator->errors()->add('finished_at', __('race.refusal.before_round_start'));
            }
        }];
    }
}
