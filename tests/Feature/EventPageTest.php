<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Participant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EventPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    #[Test]
    public function it_shows_an_open_event_to_a_guest(): void
    {
        Event::factory()->registration()->create([
            'name' => 'Backyard des 40 ans',
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Event')
                ->where('event.name', 'Backyard des 40 ans')
                ->where('event.status', 'registration')
                ->where('canRegister', true)
                ->where('isRegistered', false),
        );
    }

    #[Test]
    public function it_hides_a_draft_event_from_a_guest_without_refusing_the_page(): void
    {
        Event::factory()->create();

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('event', null)
                ->where('canRegister', false),
        );
    }

    #[Test]
    public function it_answers_when_no_event_exists(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page->where('event', null));
    }

    #[Test]
    public function it_shows_a_draft_event_to_the_manager(): void
    {
        Event::factory()->create(['name' => 'Édition en préparation']);

        $this->actingAs(User::factory()->manager()->create())
            ->get(route('home'))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('event.name', 'Édition en préparation')
                    ->where('event.status', 'draft'),
            );
    }

    #[Test]
    public function it_closes_registrations_once_the_race_is_running(): void
    {
        Event::factory()->running()->create();

        $this->get(route('home'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('canRegister', false));
    }

    #[Test]
    public function it_reports_the_registration_the_runner_holds(): void
    {
        $event = Event::factory()->registration()->create();
        $runner = User::factory()->participant()->create();
        Participant::factory()->for($event)->for($runner)->create();

        $this->actingAs($runner)
            ->get(route('home'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('isRegistered', true));
    }

    #[Test]
    public function it_splits_the_first_start_into_the_two_controls_the_screen_renders(): void
    {
        Event::factory()->registration()->create([
            'first_start_at' => '2026-09-12 13:00:00',
        ]);

        $this->get(route('home'))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('event.start_date', '2026-09-12')
                    ->where('event.start_time', '13:00'),
            );
    }
}
