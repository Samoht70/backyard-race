<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Models\Event;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * The default state is a COMPLETE draft, so every later state inherits an event
 * that could legitimately have been started. `incomplete()` is the explicit
 * opposite: without it, a running event with no first start time would be
 * buildable by accident and BR-04's tests would rest on impossible data.
 *
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Backyard '.fake()->lastName(),
            'description' => fake()->paragraph(),
            'briefing' => __('briefing.default'),
            'status' => EventStatus::Draft,
            'first_start_at' => CarbonImmutable::parse('next saturday 13:00'),
            'lap_distance_meters' => 6706,
            'lap_duration_minutes' => 60,
            'address' => fake()->address(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'max_participants' => 30,
        ];
    }

    public function incomplete(): static
    {
        return $this->state(fn (array $attributes): array => [
            'first_start_at' => null,
            'lap_distance_meters' => null,
            'lap_duration_minutes' => null,
        ]);
    }

    public function registration(): static
    {
        return $this->withStatus(EventStatus::Registration);
    }

    public function running(): static
    {
        return $this->withStatus(EventStatus::Running);
    }

    public function finished(): static
    {
        return $this->withStatus(EventStatus::Finished);
    }

    private function withStatus(EventStatus $status): static
    {
        return $this->state(fn (array $attributes): array => ['status' => $status]);
    }
}
