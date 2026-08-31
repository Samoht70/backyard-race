<?php

namespace App\Models;

use Database\Factories\ScheduleSegmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $event_id
 * @property int $from_round_number
 * @property int $lap_duration_minutes
 */
#[Fillable(['event_id', 'from_round_number', 'lap_duration_minutes'])]
class ScheduleSegment extends Model
{
    /** @use HasFactory<ScheduleSegmentFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'from_round_number' => 'integer',
            'lap_duration_minutes' => 'integer',
        ];
    }
}
