<?php

namespace App\Models;

use App\Casts\UtcDateTime;
use Database\Factories\RoundFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['event_id', 'number', 'starts_at', 'deadline_at'])]
class Round extends Model
{
    /** @use HasFactory<RoundFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return HasMany<Lap, $this>
     */
    public function laps(): HasMany
    {
        return $this->hasMany(Lap::class);
    }

    /**
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
