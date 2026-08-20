<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Models\Participant;
use App\Models\User;
use Database\Seeders\EventSeeder;
use Database\Seeders\ParticipantSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ParticipantSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_every_confirmed_participant_a_distinct_bib_number(): void
    {
        $this->seedRunners();

        $numbers = Participant::query()
            ->where('status', RegistrationStatus::Confirmed)
            ->pluck('bib_number');

        $this->assertNotEmpty($numbers);
        $this->assertCount(0, $numbers->filter(fn (?int $number): bool => $number === null));
        $this->assertSame($numbers->count(), $numbers->unique()->count());
    }

    #[Test]
    public function it_leaves_the_pending_registrations_without_a_number(): void
    {
        $this->seedRunners();

        $pending = Participant::query()
            ->where('status', RegistrationStatus::Pending)
            ->pluck('bib_number');

        $this->assertNotEmpty($pending);
        $this->assertCount($pending->count(), $pending->filter(fn (?int $number): bool => $number === null));
    }

    #[Test]
    public function it_does_not_register_anyone_twice_on_a_second_run(): void
    {
        $this->seedRunners();
        $before = Participant::query()->count();

        $this->seed(ParticipantSeeder::class);

        $this->assertSame($before, Participant::query()->count());
    }

    private function seedRunners(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(EventSeeder::class);

        User::factory()->participant()->count(20)->create();

        $this->seed(ParticipantSeeder::class);
    }
}
