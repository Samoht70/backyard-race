<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RunnerSearchRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return ['q' => ['nullable', 'string']];
    }

    protected function prepareForValidation(): void
    {
        if (! is_string($this->input('q'))) {
            $this->merge(['q' => null]);
        }
    }

    public function term(): ?string
    {
        $term = trim((string) $this->string('q'));
        $length = mb_strlen($term);

        if ($length === 0) {
            return null;
        }

        if ($length === 1 && ! ctype_digit($term)) {
            return null;
        }

        return $term;
    }
}
