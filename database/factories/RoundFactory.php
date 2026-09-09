<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Round;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Round>
 */
class RoundFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = CarbonImmutable::parse('next saturday 13:00');

        return [
            'event_id' => Event::factory()->running(),
            'number' => 1,
            'starts_at' => $startsAt,
            'deadline_at' => $startsAt->addHour(),
        ];
    }

    public function numbered(int $number): static
    {
        return $this->state(fn (array $attributes): array => ['number' => $number]);
    }

    public function startingAt(CarbonImmutable $startsAt, int $durationMinutes = 60): static
    {
        return $this->state(fn (array $attributes): array => [
            'starts_at' => $startsAt,
            'deadline_at' => $startsAt->addMinutes($durationMinutes),
        ]);
    }
}
