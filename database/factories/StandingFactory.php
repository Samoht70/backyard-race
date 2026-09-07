<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Participant;
use App\Models\Standing;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Standing>
 */
class StandingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'participant_id' => Participant::factory(),
            'rank' => 1,
            'bib_number' => fake()->numberBetween(1, 99),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'validated_laps' => fake()->numberBetween(1, 20),
            'exit_reason' => null,
        ];
    }
}
