<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Participant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\RegistersRunners;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase, RegistersRunners;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    #[Test]
    public function it_sends_a_guest_to_the_login_page(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function it_announces_a_registration_still_waiting_for_confirmation(): void
    {
        $runner = $this->runnerOf($this->openEvent());

        $this->actingAs($runner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('Dashboard')
                    ->where('registration.status', 'pending')
                    ->where('registration.bib_label', null),
            );
    }

    #[Test]
    public function it_shows_the_bib_number_once_the_registration_is_confirmed(): void
    {
        $event = $this->openEvent();
        $runner = User::factory()->participant()->create();
        Participant::factory()->confirmed()->withBib(7)->create([
            'event_id' => $event->id,
            'user_id' => $runner->id,
        ]);

        $this->actingAs($runner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('registration.status', 'confirmed')
                    ->where('registration.bib_label', '007'),
            );
    }

    #[Test]
    public function it_keeps_a_cancelled_registration_on_the_home_screen(): void
    {
        $event = $this->openEvent();
        $runner = User::factory()->participant()->create();
        Participant::factory()->cancelled()->create([
            'event_id' => $event->id,
            'user_id' => $runner->id,
        ]);

        $this->actingAs($runner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('registration.status', 'cancelled')
                    ->where('registration.status_label', 'Annulée'),
            );
    }

    #[Test]
    public function it_carries_no_registration_for_an_account_without_one(): void
    {
        $this->openEvent();

        $this->actingAs(User::factory()->participant()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('registration', null)
                    ->where('event.status', 'registration'),
            );
    }

    #[Test]
    public function it_still_answers_when_no_event_exists(): void
    {
        $this->assertDatabaseCount('events', 0);

        $this->actingAs(User::factory()->participant()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('Dashboard')
                    ->where('event', null)
                    ->where('registration', null),
            );
    }

    #[Test]
    public function it_serves_a_manager_who_also_holds_a_registration(): void
    {
        $event = Event::factory()->create();
        $manager = User::factory()->manager()->create();
        Participant::factory()->create([
            'event_id' => $event->id,
            'user_id' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('event.status', 'draft')
                    ->where('registration.status', 'pending'),
            );
    }

    private function runnerOf(Event $event): User
    {
        $runner = User::factory()->participant()->create();

        Participant::factory()->create([
            'event_id' => $event->id,
            'user_id' => $runner->id,
        ]);

        return $runner;
    }
}
