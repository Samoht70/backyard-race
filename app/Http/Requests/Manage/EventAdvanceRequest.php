<?php

namespace App\Http\Requests\Manage;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class EventAdvanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('advance', $this->event()) === true;
    }

    /**
     * `to` is not decoration: it makes the action idempotent against a stale
     * tab, which would otherwise push the race one step further than intended.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['to' => ['required', Rule::enum(EventStatus::class)]];
    }

    /**
     * The refusal reaches the manager as a validation error rather than as the
     * exception, which would leave the SPA for an untranslated error page.
     * Both ask the same state, so the two cannot disagree.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $event = $this->event();
            $lifecycle = $event->lifecycle();

            if ($lifecycle->nextStatus()?->value !== $this->string('to')->value()) {
                $validator->errors()->add('to', __('event.refusal.illegal_transition'));

                return;
            }

            foreach ($lifecycle->refusals($event) as $refusal) {
                $validator->errors()->add('to', $refusal);
            }
        }];
    }

    private function event(): Event
    {
        return Event::query()->firstOrFail();
    }
}
