<?php

namespace App\Models;

use App\Casts\UtcDateTime;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One collective round: every runner leaves at `starts_at` and is out unless
 * back before `deadline_at`. Unlike Event, every attribute is fillable — no
 * request reaches this model, its only writer is App\Actions\OpenDueRounds.
 *
 * @property int $id
 * @property int $event_id
 * @property int $number
 * @property CarbonImmutable $starts_at
 * @property CarbonImmutable $deadline_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['event_id', 'number', 'starts_at', 'deadline_at'])]
class Round extends Model
{
    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'starts_at' => UtcDateTime::class,
            'deadline_at' => UtcDateTime::class,
        ];
    }
}
