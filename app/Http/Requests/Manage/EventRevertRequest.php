<?php

namespace App\Http\Requests\Manage;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class EventRevertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('revert', $this->event()) === true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['to' => ['required', Rule::enum(EventStatus::class)]];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $event = $this->event();
            $lifecycle = $event->lifecycle();

            if ($lifecycle->previousStatus()?->value !== $this->string('to')->value()) {
                $validator->errors()->add('to', __('event.refusal.illegal_transition'));

                return;
            }

            foreach ($lifecycle->revertRefusals($event) as $refusal) {
                $validator->errors()->add('to', $refusal);
            }
        }];
    }

    private function event(): Event
    {
        return Event::query()->firstOrFail();
    }
}
