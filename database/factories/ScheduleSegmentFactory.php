<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\ScheduleSegment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScheduleSegment>
 */
class ScheduleSegmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory()->running(),
            'from_round_number' => 2,
            'lap_duration_minutes' => 55,
        ];
    }

    public function from(int $roundNumber, int $lapDurationMinutes): static
    {
        return $this->state(fn (array $attributes): array => [
            'from_round_number' => $roundNumber,
            'lap_duration_minutes' => $lapDurationMinutes,
        ]);
    }
}
