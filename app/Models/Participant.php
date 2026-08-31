<?php

namespace App\Models;

use App\Enums\RegistrationStatus;
use App\Enums\RegistrationTransition;
use App\Models\Concerns\HasRaceStatus;
use App\Services\RegistrationLifecycle\RegistrationLifecycleFactory;
use App\Services\RegistrationLifecycle\RegistrationLifecycleState;
use Carbon\CarbonImmutable;
use Database\Factories\ParticipantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $event_id
 * @property int $user_id
 * @property RegistrationStatus $status
 * @property int|null $bib_number
 * @property string $phone
 * @property CarbonImmutable $birth_date
 * @property string|null $pps_number
 * @property string $emergency_contact_name
 * @property string $emergency_contact_phone
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'phone',
    'birth_date',
    'pps_number',
    'emergency_contact_name',
    'emergency_contact_phone',
    'notes',
])]
class Participant extends Model
{
    /** @use HasFactory<ParticipantFactory> */
    use HasFactory;

    use HasRaceStatus;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['status' => RegistrationStatus::Pending->value];

    public function lifecycle(): RegistrationLifecycleState
    {
        return app(RegistrationLifecycleFactory::class)->fromStatus($this->status);
    }

    /**
     * @return list<string>
     */
    public function allowedTransitionValues(): array
    {
        return array_map(
            fn (RegistrationTransition $transition): string => $transition->value,
            $this->lifecycle()->allowedTransitions(),
        );
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RegistrationStatus::class,
            'bib_number' => 'integer',
            'birth_date' => 'immutable_date',
        ];
    }
}
