<?php

namespace App\Models;

use App\Enums\EventStatus;
use App\Services\EventLifecycle\EventLifecycleFactory;
use App\Services\EventLifecycle\EventLifecycleState;
use Carbon\CarbonImmutable;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * The root object of the product: a single event the whole application serves.
 *
 * `status` is deliberately absent from the fillable list — it is the only thing
 * standing between a crafted request and a race declared finished.
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
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
     * A brand new event is a draft. The column default alone would leave an
     * unsaved firstOrNew() without a status, and the lifecycle would have no
     * state to build.
     *
     * @var array<string, mixed>
     */
    protected $attributes = ['status' => EventStatus::Draft->value];

    public function lifecycle(): EventLifecycleState
    {
        return app(EventLifecycleFactory::class)->fromStatus($this->status);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => EventStatus::class,
            'first_start_at' => 'immutable_datetime',
            'lap_distance_meters' => 'integer',
            'lap_duration_minutes' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
            'max_participants' => 'integer',
        ];
    }
}
