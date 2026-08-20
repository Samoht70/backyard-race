<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\MediaLibrary\MediaCollections\Models\Media as SpatieMedia;

/**
 * @property-read string $temporary_url
 * @property-read string $download_url
 */
class Media extends SpatieMedia
{
    private const LINK_LIFETIME_DAYS = 7;

    /**
     * @return Attribute<string, never>
     */
    protected function temporaryUrl(): Attribute
    {
        return Attribute::get(fn (): string => $this->signedUrl());
    }

    /**
     * @return Attribute<string, never>
     */
    protected function downloadUrl(): Attribute
    {
        return Attribute::get(fn (): string => $this->signedUrl([
            'ResponseContentDisposition' => 'attachment; filename="'.$this->file_name.'"',
        ]));
    }

    /**
     * @param  array<string, string>  $options
     *
     * @throws \RuntimeException when the media disk cannot sign a temporary URL
     */
    private function signedUrl(array $options = []): string
    {
        return $this->getTemporaryUrl(now()->addDays(self::LINK_LIFETIME_DAYS), '', $options);
    }
}
