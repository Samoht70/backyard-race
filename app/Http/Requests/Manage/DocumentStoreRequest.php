<?php

namespace App\Http\Requests\Manage;

use App\Models\Document;
use App\Models\Event;
use App\Rules\DocumentFile;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class DocumentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', [Document::class, $this->event()]) === true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'file' => ['required', new DocumentFile],
        ];
    }

    public function title(): string
    {
        return $this->string('title')->trim()->toString();
    }

    public function description(): ?string
    {
        $description = $this->string('description')->trim()->toString();

        return $description === '' ? null : $description;
    }

    public function document(): UploadedFile
    {
        $file = $this->file('file');

        abort_unless($file instanceof UploadedFile, 422);

        return $file;
    }

    public function event(): Event
    {
        return Event::query()->firstOrFail();
    }
}
