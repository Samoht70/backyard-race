<?php

namespace App\Models;

use App\Casts\UtcDateTime;
use App\Enums\EventStatus;
use App\Enums\RegistrationStatus;
use App\Services\EventLifecycle\EventLifecycleFactory;
use App\Services\EventLifecycle\EventLifecycleState;
use Carbon\CarbonImmutable;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string|null $briefing
 * @property EventStatus $status
 * @property CarbonImmutable|null $first_start_at
 * @property int|null $lap_distance_meters
 * @property int|null $lap_duration_minutes
 * @property string|null $address
 * @property float|null $latitude
 * @property float|null $longitude
 * @property int|null $max_participants
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'name',
    'description',
    'first_start_at',
    'lap_distance_meters',
    'lap_duration_minutes',
    'address',
    'latitude',
    'longitude',
    'max_participants',
])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['status' => EventStatus::Draft->value];

    public static function current(): self
    {
        return self::query()->firstOrFail();
    }

    public static function currentOrNew(): self
    {
        return self::query()->firstOrNew();
    }

    public static function currentOrNull(): ?self
    {
        return self::query()->first();
    }

    public function lifecycle(): EventLifecycleState
    {
        return app(EventLifecycleFactory::class)->fromStatus($this->status);
    }

    /**
     * @return HasMany<Round, $this>
     */
    public function rounds(): HasMany
    {
        return $this->hasMany(Round::class);
    }

    /**
     * @return HasMany<ScheduleSegment, $this>
     */
    public function scheduleSegments(): HasMany
    {
        return $this->hasMany(ScheduleSegment::class)->orderBy('from_round_number');
    }

    /**
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class)->orderBy('title');
    }

    /**
     * @return HasMany<Participant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function confirmedParticipantsCount(): int
    {
        return $this->participants()
            ->where('status', RegistrationStatus::Confirmed)
            ->count();
    }

    public function isFull(): bool
    {
        return $this->max_participants !== null
            && $this->confirmedParticipantsCount() >= $this->max_participants;
    }

    public function acceptsRegistrations(): bool
    {
        return $this->lifecycle()->allowsRegistration() && ! $this->isFull();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => EventStatus::class,
            'first_start_at' => UtcDateTime::class,
            'lap_distance_meters' => 'integer',
            'lap_duration_minutes' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
            'max_participants' => 'integer',
        ];
    }
}
