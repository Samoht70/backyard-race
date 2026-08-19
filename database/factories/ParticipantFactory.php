<?php

namespace Database\Factories;

use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Participant>
 */
class ParticipantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'user_id' => User::factory(),
            'status' => RegistrationStatus::Pending,
            'phone' => fake()->phoneNumber(),
            'birth_date' => fake()->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_phone' => fake()->phoneNumber(),
            'notes' => null,
        ];
    }

    public function confirmed(): static
    {
        return $this->withStatus(RegistrationStatus::Confirmed);
    }

    public function cancelled(): static
    {
        return $this->withStatus(RegistrationStatus::Cancelled);
    }

    private function withStatus(RegistrationStatus $status): static
    {
        return $this->state(fn (array $attributes): array => ['status' => $status]);
    }
}
