<?php

namespace App\Models;

use App\Rules\DocumentFile;
use Carbon\CarbonImmutable;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property int $event_id
 * @property string $title
 * @property string|null $description
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'title',
    'description',
])]
class Document extends Model implements HasMedia
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory;

    use InteractsWithMedia;

    public const FILE_COLLECTION = 'file';

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::FILE_COLLECTION)
            ->singleFile()
            ->acceptsMimeTypes(DocumentFile::mimeTypes());
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function file(): ?Media
    {
        $file = $this->getFirstMedia(self::FILE_COLLECTION);

        return $file instanceof Media ? $file : null;
    }
}
