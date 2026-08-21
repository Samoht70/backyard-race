<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EventPageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_redirects_a_guest_to_the_login_page(): void
    {
        Event::factory()->registration()->create();

        $this->get(route('event.show'))->assertRedirect(route('login'));
    }

    #[Test]
    public function it_refuses_a_draft_event_to_a_participant(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->create();

        $this->actingAs(User::factory()->participant()->create())
            ->get(route('event.show'))
            ->assertForbidden();
    }

    #[Test]
    public function it_shows_a_draft_event_to_the_manager(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->create();

        $this->actingAs(User::factory()->manager()->create())
            ->get(route('event.show'))
            ->assertOk();
    }

    #[Test]
    public function it_shows_an_open_event_to_a_participant(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->registration()->create([
            'name' => 'Backyard des 40 ans',
        ]);

        $response = $this->actingAs(User::factory()->participant()->create())
            ->get(route('event.show'));

        $response->assertOk();
        $response->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Event')
                ->where('event.name', 'Backyard des 40 ans')
                ->where('event.status', 'registration')
                ->where('canRegister', true),
        );
    }

    #[Test]
    public function it_closes_registrations_once_the_race_is_running(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->running()->create();

        $this->actingAs(User::factory()->participant()->create())
            ->get(route('event.show'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('canRegister', false));
    }

    #[Test]
    public function it_splits_the_first_start_into_the_two_controls_the_screen_renders(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->registration()->create([
            'first_start_at' => '2026-09-12 13:00:00',
        ]);

        $this->actingAs(User::factory()->participant()->create())
            ->get(route('event.show'))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('event.start_date', '2026-09-12')
                    ->where('event.start_time', '13:00'),
            );
    }

    #[Test]
    public function it_returns_not_found_when_no_event_exists(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->actingAs(User::factory()->manager()->create())
            ->get(route('event.show'))
            ->assertNotFound();
    }
}
