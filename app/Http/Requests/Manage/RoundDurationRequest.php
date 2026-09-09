<?php

namespace App\Http\Requests\Manage;

use App\Actions\OpenDueRounds;
use App\Enums\ScheduleChange;
use App\Models\Event;
use App\Services\RaceSchedule\NextRound;
use App\Services\RaceSchedule\ResolveNextRound;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RoundDurationRequest extends FormRequest
{
    private ?Event $event = null;

    public function authorize(): bool
    {
        return $this->user()?->can('changeSchedule', $this->event()) === true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'from' => ['required', 'integer', 'min:1', 'max:65535'],
            'lap_duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'change' => ['required', Rule::enum(ScheduleChange::class)],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $next = $this->nextRound();

            if ($next === null) {
                $validator->errors()->add('from', __('race.refusal.no_schedule'));

                return;
            }

            if ($this->integer('from') < $next->number) {
                $validator->errors()->add('from', __('race.refusal.round_started'));
            }
        }];
    }

    private function nextRound(): ?NextRound
    {
        $event = $this->event();

        app(OpenDueRounds::class)($event);

        return app(ResolveNextRound::class)($event);
    }

    private function event(): Event
    {
        return $this->event ??= Event::current();
    }
}
