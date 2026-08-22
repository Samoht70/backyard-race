<?php

namespace App\Http\Requests\Manage;

use App\Models\Event;
use App\Support\Briefing;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BriefingUpdateRequest extends FormRequest
{
    private ?Event $event = null;

    public function authorize(): bool
    {
        return $this->user()?->can('updateBriefing', $this->event()) === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'briefing' => Briefing::clean($this->string('briefing')->toString()),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'briefing' => ['required', 'string', 'max:10000'],
        ];
    }

    public function briefing(): string
    {
        return $this->string('briefing')->toString();
    }

    public function event(): Event
    {
        return $this->event ??= Event::current();
    }
}
