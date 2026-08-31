<?php

namespace App\Models;

use App\Casts\UtcDateTime;
use App\Enums\LapStatus;
use Carbon\CarbonImmutable;
use Database\Factories\LapFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $participant_id
 * @property int $round_id
 * @property LapStatus $status
 * @property CarbonImmutable|null $validated_at
 */
#[Fillable(['participant_id', 'round_id', 'status', 'validated_at'])]
class Lap extends Model
{
    /** @use HasFactory<LapFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['status' => LapStatus::Pending->value];

    /**
     * @return BelongsTo<Participant, $this>
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    /**
     * @return BelongsTo<Round, $this>
     */
    public function round(): BelongsTo
    {
        return $this->belongsTo(Round::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => LapStatus::class,
            'validated_at' => UtcDateTime::class,
        ];
    }
}
