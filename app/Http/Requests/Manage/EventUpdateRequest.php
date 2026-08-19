<?php

namespace App\Http\Requests\Manage;

use App\Models\Event;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->event()) === true;
    }

    /**
     * The screen collects a date and a time; the schema stores one instant,
     * because BR-04 computes lap starts from it and a split pair cannot say
     * which day a late lap falls on.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled(['start_date', 'start_time'])) {
            $this->merge([
                'first_start_at' => $this->string('start_date').' '.$this->string('start_time'),
            ]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:5000'],
            'first_start_at' => [
                'nullable',
                'date',
                Rule::prohibitedIf($this->isFrozen('first_start_at')),
            ],
            'lap_distance_meters' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'lap_duration_minutes' => [
                'nullable',
                'integer',
                'min:1',
                'max:1440',
                Rule::prohibitedIf($this->isFrozen('lap_duration_minutes')),
            ],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'max_participants' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ];
    }

    private function isFrozen(string $attribute): bool
    {
        return in_array($attribute, $this->event()->lifecycle()->frozenAttributes(), true);
    }

    private function event(): Event
    {
        return Event::query()->firstOrNew();
    }
}
