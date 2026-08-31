<?php

namespace Database\Factories;

use App\Enums\LapStatus;
use App\Models\Lap;
use App\Models\Participant;
use App\Models\Round;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lap>
 */
class LapFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'round_id' => Round::factory(),
            'participant_id' => fn (array $attributes): Factory => Participant::factory()
                ->confirmed()
                ->state(['event_id' => Round::query()->whereKey($attributes['round_id'])->sole()->event_id]),
            'status' => LapStatus::Pending,
            'validated_at' => null,
        ];
    }

    public function validated(?CarbonImmutable $at = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => LapStatus::Validated,
            'validated_at' => $at ?? CarbonImmutable::now(),
        ]);
    }

    public function eliminated(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => LapStatus::Eliminated,
            'validated_at' => null,
        ]);
    }
}
