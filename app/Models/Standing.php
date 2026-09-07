<?php

namespace App\Models;

use App\Enums\ExitReason;
use App\Enums\RunnerStatus;
use Carbon\CarbonImmutable;
use Database\Factories\StandingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $event_id
 * @property int $participant_id
 * @property int $rank
 * @property int|null $bib_number
 * @property string $first_name
 * @property string $last_name
 * @property int $validated_laps
 * @property ExitReason|null $exit_reason
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'participant_id',
    'rank',
    'bib_number',
    'first_name',
    'last_name',
    'validated_laps',
    'exit_reason',
])]
class Standing extends Model
{
    /** @use HasFactory<StandingFactory> */
    use HasFactory;

    public function runnerStatus(): RunnerStatus
    {
        return $this->exit_reason?->runnerStatus() ?? RunnerStatus::Finished;
    }

    public function coveredMeters(?int $lapDistanceMeters): ?int
    {
        return $lapDistanceMeters === null
            ? null
            : $this->validated_laps * $lapDistanceMeters;
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<Participant, $this>
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_id' => 'integer',
            'participant_id' => 'integer',
            'rank' => 'integer',
            'bib_number' => 'integer',
            'validated_laps' => 'integer',
            'exit_reason' => ExitReason::class,
        ];
    }
}
