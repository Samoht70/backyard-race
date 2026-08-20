<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class DocumentFile implements ValidationRule
{
    /**
     * @var array<string, list<string>>
     */
    private const ACCEPTED = [
        'pdf' => ['application/pdf'],
        'png' => ['image/png'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'webp' => ['image/webp'],
        'gpx' => ['application/gpx+xml', 'application/xml', 'text/xml'],
    ];

    /**
     * @return list<string>
     */
    public static function extensions(): array
    {
        return array_keys(self::ACCEPTED);
    }

    /**
     * @return list<string>
     */
    public static function mimeTypes(): array
    {
        return array_values(array_unique(array_merge(...array_values(self::ACCEPTED))));
    }

    public static function maxBytes(): int
    {
        /** @var int $bytes */
        $bytes = config('media-library.max_file_size');

        return $bytes;
    }

    public function validate(string $attribute, mixed $upload, Closure $fail): void
    {
        if (! $upload instanceof UploadedFile || ! $upload->isValid()) {
            $fail('document.file.unreadable')->translate();

            return;
        }

        $extension = strtolower($upload->getClientOriginalExtension());

        if (! array_key_exists($extension, self::ACCEPTED)) {
            $fail('document.file.extension')->translate(['extensions' => implode(', ', self::extensions())]);

            return;
        }

        if (! in_array($upload->getMimeType(), self::ACCEPTED[$extension], true)) {
            $fail('document.file.mismatch')->translate(['extension' => $extension]);

            return;
        }

        if ($upload->getSize() > self::maxBytes()) {
            $fail('document.file.too_large')->translate(['max' => self::maxMegabytes()]);
        }
    }

    public static function maxMegabytes(): int
    {
        return (int) round(self::maxBytes() / 1024 / 1024);
    }
}
