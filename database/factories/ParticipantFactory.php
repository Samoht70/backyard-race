<?php

namespace Database\Factories;

use App\Actions\NextBibNumber;
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
        return $this->withStatus(RegistrationStatus::Confirmed)
            ->afterCreating(function (Participant $participant): void {
                if ($participant->bib_number !== null) {
                    return;
                }

                $participant->forceFill([
                    'bib_number' => app(NextBibNumber::class)($participant->event),
                ])->save();
            });
    }

    public function withBib(int $number): static
    {
        return $this->state(fn (array $attributes): array => ['bib_number' => $number]);
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
