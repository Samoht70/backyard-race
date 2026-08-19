<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\Participant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_registers_a_runner_as_pending(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->openEvent();

        $this->actingAs($this->runner())
            ->post(route('registration.store'), $this->payload())
            ->assertRedirect(route('registration.show'))
            ->assertSessionHas('inertia.flash_data.toast.message', 'Inscription enregistrée.');

        $participant = Participant::query()->sole();

        $this->assertSame(RegistrationStatus::Pending, $participant->status);
        $this->assertSame('06 12 34 56 78', $participant->phone);
    }

    #[Test]
    public function it_shows_the_form_with_the_confirmed_seat_count(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $event = $this->openEvent(['max_participants' => 40]);
        Participant::factory()->confirmed()->count(12)->create(['event_id' => $event->id]);

        $this->actingAs($this->runner())
            ->get(route('registration.create'))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('registration/Create')
                    ->where('event.confirmed_participants', 12)
                    ->where('event.max_participants', 40),
            );
    }

    #[Test]
    public function it_refuses_a_second_registration_from_the_same_account(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $event = $this->openEvent();
        $runner = $this->runner();
        Participant::factory()->create(['event_id' => $event->id, 'user_id' => $runner->id]);

        $this->actingAs($runner)
            ->post(route('registration.store'), $this->payload())
            ->assertForbidden();

        $this->assertSame(1, Participant::query()->count());
    }

    #[Test]
    public function it_lets_the_database_refuse_a_second_registration_for_one_account(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $event = $this->openEvent();
        $runner = $this->runner();
        Participant::factory()->create(['event_id' => $event->id, 'user_id' => $runner->id]);

        $this->expectException(QueryException::class);

        Participant::factory()->create(['event_id' => $event->id, 'user_id' => $runner->id]);
    }

    #[Test]
    public function it_shows_the_owner_their_registration_with_its_status(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $event = $this->openEvent();
        $runner = $this->runner();
        Participant::factory()->confirmed()->create([
            'event_id' => $event->id,
            'user_id' => $runner->id,
        ]);

        $this->actingAs($runner)
            ->get(route('registration.show'))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('registration/Show')
                    ->where('registration.status', 'confirmed')
                    ->where('registration.status_label', 'Confirmée')
                    ->where('registration.first_name', $runner->first_name)
                    ->where('canEdit', false),
            );
    }

    #[Test]
    public function it_refuses_a_registration_once_the_confirmed_seats_are_taken(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $event = $this->openEvent(['max_participants' => 2]);
        Participant::factory()->confirmed()->count(2)->create(['event_id' => $event->id]);

        $this->actingAs($this->runner())
            ->post(route('registration.store'), $this->payload())
            ->assertSessionHasErrors(['event' => 'L’événement est complet : toutes les places confirmées sont prises.']);

        $this->assertSame(2, Participant::query()->count());
    }

    #[Test]
    public function it_never_calls_an_event_without_a_cap_full(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $event = $this->openEvent(['max_participants' => null]);
        Participant::factory()->confirmed()->count(30)->create(['event_id' => $event->id]);

        $this->actingAs($this->runner())
            ->post(route('registration.store'), $this->payload())
            ->assertSessionHasNoErrors();

        $this->assertSame(31, Participant::query()->count());
    }

    #[Test]
    public function it_counts_only_the_confirmed_registrations_towards_the_cap(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $event = $this->openEvent(['max_participants' => 2]);
        Participant::factory()->count(2)->create(['event_id' => $event->id]);

        $this->actingAs($this->runner())
            ->post(route('registration.store'), $this->payload())
            ->assertSessionHasNoErrors();
    }

    #[Test]
    public function it_lets_a_runner_correct_a_pending_registration(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $event = $this->openEvent();
        $runner = $this->runner();
        Participant::factory()->create(['event_id' => $event->id, 'user_id' => $runner->id]);

        $this->actingAs($runner)
            ->put(route('registration.update'), $this->payload(['phone' => '07 98 76 54 32']))
            ->assertRedirect(route('registration.show'));

        $this->assertSame('07 98 76 54 32', Participant::query()->sole()->phone);
    }

    #[Test]
    public function it_freezes_a_confirmed_registration_against_its_own_runner(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $event = $this->openEvent();
        $runner = $this->runner();
        Participant::factory()->confirmed()->create([
            'event_id' => $event->id,
            'user_id' => $runner->id,
            'phone' => '06 12 34 56 78',
        ]);

        $this->actingAs($runner)
            ->put(route('registration.update'), $this->payload(['phone' => '07 98 76 54 32']))
            ->assertForbidden();

        $this->assertSame('06 12 34 56 78', Participant::query()->sole()->phone);
    }

    #[Test]
    public function it_shows_a_runner_their_own_registration_and_never_another_one(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $event = $this->openEvent();
        Participant::factory()->create(['event_id' => $event->id]);

        $this->actingAs($this->runner())
            ->get(route('registration.show'))
            ->assertRedirect(route('registration.create'));
    }

    #[Test]
    public function it_sends_a_registered_runner_from_the_form_back_to_their_registration(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $event = $this->openEvent();
        $runner = $this->runner();
        Participant::factory()->create(['event_id' => $event->id, 'user_id' => $runner->id]);

        $this->actingAs($runner)
            ->get(route('registration.create'))
            ->assertRedirect(route('registration.show'));
    }

    #[Test]
    public function it_closes_the_form_while_the_event_is_a_draft(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->create();

        $this->actingAs($this->runner())
            ->get(route('registration.create'))
            ->assertForbidden();
    }

    #[Test]
    public function it_closes_the_form_once_the_race_is_running(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->running()->create();

        $this->actingAs($this->runner())
            ->post(route('registration.store'), $this->payload())
            ->assertForbidden();
    }

    #[Test]
    public function it_refuses_a_birth_date_in_the_future(): void
    {
        $this->assertRejects(['birth_date' => now()->addDay()->format('Y-m-d')], 'birth_date');
    }

    #[Test]
    public function it_refuses_a_runner_under_eighteen(): void
    {
        $this->assertRejects(['birth_date' => now()->subYears(17)->format('Y-m-d')], 'birth_date');
    }

    #[Test]
    public function it_refuses_a_registration_without_an_emergency_contact(): void
    {
        $this->assertRejects(['emergency_contact_name' => ''], 'emergency_contact_name');
    }

    #[Test]
    public function it_refuses_a_registration_without_a_phone_number(): void
    {
        $this->assertRejects(['phone' => ''], 'phone');
    }

    #[Test]
    public function it_never_lets_the_status_be_mass_assigned(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->openEvent();

        $this->actingAs($this->runner())
            ->post(route('registration.store'), $this->payload(['status' => 'confirmed']));

        $this->assertSame(RegistrationStatus::Pending, Participant::query()->sole()->status);
    }

    #[Test]
    public function it_never_lets_the_registration_be_attached_to_another_account(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->openEvent();
        $runner = $this->runner();
        $other = $this->runner();

        $this->actingAs($runner)
            ->post(route('registration.store'), $this->payload(['user_id' => $other->id]));

        $this->assertSame($runner->id, Participant::query()->sole()->user_id);
    }

    #[Test]
    public function it_stops_the_manager_from_shrinking_the_event_below_the_confirmed_runners(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $event = $this->openEvent(['max_participants' => 40]);
        Participant::factory()->confirmed()->count(12)->create(['event_id' => $event->id]);

        $this->actingAs(User::factory()->manager()->create())
            ->put(route('manage.event.update'), [
                'name' => 'Backyard des 40 ans',
                'start_date' => '2026-09-12',
                'start_time' => '13:00',
                'lap_distance_meters' => 6706,
                'lap_duration_minutes' => 60,
                'max_participants' => 11,
            ])
            ->assertSessionHasErrors('max_participants');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function assertRejects(array $overrides, string $field): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->openEvent();

        $this->actingAs($this->runner())
            ->post(route('registration.store'), $this->payload($overrides))
            ->assertSessionHasErrors($field);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'phone' => '06 12 34 56 78',
            'birth_date' => '1986-04-17',
            'emergency_contact_name' => 'Camille Berger',
            'emergency_contact_phone' => '06 87 65 43 21',
            'notes' => 'Allergie aux fruits à coque.',
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function openEvent(array $attributes = []): Event
    {
        return Event::factory()->registration()->create($attributes);
    }

    private function runner(): User
    {
        return User::factory()->participant()->create();
    }
}
