<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Models\Participant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\RegistersRunners;
use Tests\TestCase;

class SharedBoardTest extends TestCase
{
    use RefreshDatabase, RegistersRunners;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    #[Test]
    public function it_carries_the_event_facts_to_every_screen(): void
    {
        $this->openEvent([
            'name' => 'Backyard des Coteaux',
            'max_participants' => 40,
            'first_start_at' => '2026-09-12 13:00:00',
        ]);

        $this->get(route('home'))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('board.name', 'Backyard des Coteaux')
                    ->where('board.max_participants', 40)
                    ->where('board.first_start_time', '13:00'),
            );
    }

    #[Test]
    public function it_counts_only_confirmed_runners_against_the_seats(): void
    {
        $event = $this->openEvent(['max_participants' => 40]);

        Participant::factory()->for($event)->for(User::factory())->create([
            'status' => RegistrationStatus::Confirmed,
        ]);
        Participant::factory()->for($event)->for(User::factory())->create([
            'status' => RegistrationStatus::Pending,
        ]);

        $this->get(route('home'))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('board.confirmed_participants', 1),
            );
    }

    #[Test]
    public function it_leaves_the_band_empty_until_an_event_exists(): void
    {
        $this->get(route('home'))
            ->assertInertia(
                fn (AssertableInertia $page) => $page->where('board', null),
            );
    }
}
