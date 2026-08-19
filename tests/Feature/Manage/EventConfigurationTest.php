<?php

namespace Tests\Feature\Manage;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EventConfigurationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_configuration_screen_to_a_manager(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->create(['name' => 'Backyard des 40 ans']);

        $response = $this->actingAs($this->manager())->get(route('manage.event.edit'));

        $response->assertOk();
        $response->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('manage/Event')
                ->where('event.name', 'Backyard des 40 ans')
                ->where('transition.current', 'draft')
                ->where('transition.next', 'registration')
                ->where('isEditable', true),
        );
    }

    #[Test]
    public function it_refuses_the_configuration_screen_to_a_participant(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->create();

        $this->actingAs(User::factory()->participant()->create())
            ->get(route('manage.event.edit'))
            ->assertForbidden();
    }

    #[Test]
    public function it_shows_an_empty_form_when_no_event_exists_yet(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->actingAs($this->manager())
            ->get(route('manage.event.edit'))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('event.name', null)
                    ->where('transition.current', 'draft'),
            );
    }

    #[Test]
    public function it_creates_the_event_on_the_first_save(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->actingAs($this->manager())
            ->put(route('manage.event.update'), $this->payload())
            ->assertRedirect(route('manage.event.edit'));

        $event = Event::query()->sole();

        $this->assertSame('Backyard des 40 ans', $event->name);
        $this->assertSame(EventStatus::Draft, $event->status);
        $this->assertSame('2026-09-12 13:00', $event->first_start_at?->format('Y-m-d H:i'));
    }

    #[Test]
    public function it_updates_the_existing_event_without_creating_a_second_one(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->create();

        $this->actingAs($this->manager())
            ->put(route('manage.event.update'), $this->payload(['name' => 'Nouveau nom']));

        $this->assertSame(1, Event::query()->count());
        $this->assertSame('Nouveau nom', Event::query()->sole()->name);
    }

    #[Test]
    public function it_refuses_a_lap_distance_of_zero(): void
    {
        $this->assertRejects(['lap_distance_meters' => 0], 'lap_distance_meters');
    }

    #[Test]
    public function it_refuses_a_negative_lap_duration(): void
    {
        $this->assertRejects(['lap_duration_minutes' => -1], 'lap_duration_minutes');
    }

    #[Test]
    public function it_refuses_a_participant_cap_below_one(): void
    {
        $this->assertRejects(['max_participants' => 0], 'max_participants');
    }

    #[Test]
    public function it_refuses_a_date_without_an_hour_instead_of_dropping_it(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->create(['first_start_at' => '2026-09-12 13:00:00']);

        $payload = $this->payload(['start_time' => '']);

        $this->actingAs($this->manager())
            ->put(route('manage.event.update'), $payload)
            ->assertSessionHasErrors('start_time');

        $this->assertSame(
            '2026-09-12 13:00',
            Event::query()->sole()->first_start_at?->format('Y-m-d H:i'),
        );
    }

    #[Test]
    public function it_refuses_an_hour_without_a_date(): void
    {
        $this->assertRejects(['start_date' => ''], 'start_date');
    }

    #[Test]
    public function it_clears_the_first_start_when_both_halves_are_emptied(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->create(['first_start_at' => '2026-09-12 13:00:00']);

        $this->actingAs($this->manager())
            ->put(route('manage.event.update'), $this->payload([
                'start_date' => '',
                'start_time' => '',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertNull(Event::query()->sole()->first_start_at);
    }

    #[Test]
    public function it_refuses_a_date_that_is_not_a_calendar_date(): void
    {
        $this->assertRejects(['start_date' => 'next tuesday'], 'start_date');
    }

    #[Test]
    public function it_accepts_an_event_without_coordinates(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->actingAs($this->manager())
            ->put(route('manage.event.update'), $this->payload([
                'latitude' => null,
                'longitude' => null,
            ]))
            ->assertSessionHasNoErrors();

        $this->assertNull(Event::query()->sole()->latitude);
    }

    #[Test]
    public function it_refuses_a_latitude_without_a_longitude(): void
    {
        $this->assertRejects(['longitude' => null], 'longitude');
    }

    #[Test]
    public function it_refuses_to_change_the_first_start_time_once_running(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->running()->create();

        $this->actingAs($this->manager())
            ->put(route('manage.event.update'), $this->payload())
            ->assertSessionHasErrors('first_start_at');
    }

    #[Test]
    public function it_refuses_to_change_the_lap_duration_once_running(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->running()->create();

        $payload = $this->payload();
        unset($payload['start_date'], $payload['start_time']);

        $this->actingAs($this->manager())
            ->put(route('manage.event.update'), $payload)
            ->assertSessionHasErrors('lap_duration_minutes');
    }

    #[Test]
    public function it_reports_the_frozen_field_when_a_stale_tab_resubmits_it(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->running()->create([
            'first_start_at' => '2026-09-12 13:00:00',
            'address' => 'Ancienne adresse',
        ]);

        $this->actingAs($this->manager())
            ->put(route('manage.event.update'), $this->payload([
                'start_date' => '2026-09-12',
                'start_time' => '13:00',
                'address' => 'Nouvelle adresse',
            ]))
            ->assertSessionHasErrors('first_start_at');

        $this->assertSame('Ancienne adresse', Event::query()->sole()->address);
    }

    #[Test]
    public function it_still_accepts_a_name_change_once_running(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->running()->create();

        $payload = $this->payload(['name' => 'Nom corrigé']);
        unset($payload['start_date'], $payload['start_time'], $payload['lap_duration_minutes']);

        $this->actingAs($this->manager())
            ->put(route('manage.event.update'), $payload)
            ->assertSessionHasNoErrors();

        $this->assertSame('Nom corrigé', Event::query()->sole()->name);
    }

    #[Test]
    public function it_refuses_every_change_once_finished(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->finished()->create(['name' => 'Intouchable']);

        $this->actingAs($this->manager())
            ->put(route('manage.event.update'), $this->payload())
            ->assertForbidden();

        $this->assertSame('Intouchable', Event::query()->sole()->name);
    }

    #[Test]
    public function it_never_lets_the_status_be_mass_assigned(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->create();

        $this->actingAs($this->manager())
            ->put(route('manage.event.update'), $this->payload(['status' => 'finished']));

        $this->assertSame(EventStatus::Draft, Event::query()->sole()->status);
    }

    #[Test]
    public function it_flashes_a_confirmation_toast(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->actingAs($this->manager())
            ->put(route('manage.event.update'), $this->payload())
            ->assertSessionHas('inertia.flash_data.toast.message', 'Configuration enregistrée.');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function assertRejects(array $overrides, string $field): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->actingAs($this->manager())
            ->put(route('manage.event.update'), $this->payload($overrides))
            ->assertSessionHasErrors($field);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'name' => 'Backyard des 40 ans',
            'description' => 'Une boucle, une heure, jusqu’au bout.',
            'start_date' => '2026-09-12',
            'start_time' => '13:00',
            'lap_distance_meters' => 6706,
            'lap_duration_minutes' => 60,
            'address' => '12 chemin des Prés',
            'latitude' => 45.764,
            'longitude' => 4.8357,
            'max_participants' => 40,
            ...$overrides,
        ];
    }

    private function manager(): User
    {
        return User::factory()->manager()->create();
    }
}
