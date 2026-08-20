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
use Tests\Concerns\RegistersRunners;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase, RegistersRunners;

    private const EMAIL = 'recrue@backyard.test';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    #[Test]
    public function it_registers_a_runner_as_pending(): void
    {
        $this->openEvent();

        $this->completeRegistration(self::EMAIL)
            ->assertRedirect(route('account.show'));

        $participant = Participant::query()->sole();

        $this->assertSame(RegistrationStatus::Pending, $participant->status);
        $this->assertSame('06 12 34 56 78', $participant->phone);
        $this->assertSame(self::EMAIL, $participant->user->email);
        $this->assertSame('Nouvelle Recrue', $participant->user->name);
    }

    #[Test]
    public function it_shows_the_confirmed_seat_count_on_the_email_step(): void
    {
        $event = $this->openEvent(['max_participants' => 40]);
        Participant::factory()->confirmed()->count(12)->create(['event_id' => $event->id]);

        $this->get(route('account.create'))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('auth/register/Start')
                    ->where('seats.confirmed', 12)
                    ->where('seats.capacity', 40),
            );
    }

    #[Test]
    public function it_refuses_the_email_step_once_the_confirmed_seats_are_taken(): void
    {
        $event = $this->openEvent(['max_participants' => 2]);
        Participant::factory()->confirmed()->count(2)->create(['event_id' => $event->id]);

        $this->post(route('account.store'), ['email' => self::EMAIL])
            ->assertSessionHasErrors(['event' => 'L’événement est complet : toutes les places confirmées sont prises.']);

        $this->assertDatabaseMissing('users', ['email' => self::EMAIL]);
    }

    #[Test]
    public function it_refuses_the_final_submission_when_the_seats_fill_in_the_meantime(): void
    {
        $event = $this->openEvent(['max_participants' => 2]);
        Participant::factory()->confirmed()->create(['event_id' => $event->id]);

        $this->get($this->registrationLink(self::EMAIL))->assertOk();

        Participant::factory()->confirmed()->create(['event_id' => $event->id]);

        $this->put(route('account.update'), $this->registrationPayload())
            ->assertSessionHasErrors('event');

        $this->assertDatabaseMissing('users', ['email' => self::EMAIL]);
    }

    #[Test]
    public function it_never_calls_an_event_without_a_cap_full(): void
    {
        $event = $this->openEvent(['max_participants' => null]);
        Participant::factory()->confirmed()->count(30)->create(['event_id' => $event->id]);

        $this->completeRegistration(self::EMAIL)->assertSessionHasNoErrors();

        $this->assertSame(31, Participant::query()->count());
    }

    #[Test]
    public function it_counts_only_the_confirmed_registrations_towards_the_cap(): void
    {
        $event = $this->openEvent(['max_participants' => 2]);
        Participant::factory()->count(2)->create(['event_id' => $event->id]);

        $this->completeRegistration(self::EMAIL)->assertSessionHasNoErrors();
    }

    #[Test]
    public function it_closes_the_email_step_while_the_event_is_a_draft(): void
    {
        Event::factory()->create();

        $this->get(route('account.create'))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('auth/register/Start')
                    ->where('open', false),
            );

        $this->post(route('account.store'), ['email' => self::EMAIL])
            ->assertSessionHasErrors(['event' => 'Les inscriptions ne sont pas ouvertes.']);
    }

    #[Test]
    public function it_closes_the_registration_form_once_the_race_is_running(): void
    {
        Event::factory()->running()->create();

        $this->get($this->registrationLink(self::EMAIL))
            ->assertRedirect(route('account.create'));

        $this->put(route('account.update'), $this->registrationPayload())
            ->assertSessionHasErrors('event');

        $this->assertDatabaseCount('users', 0);
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
    public function it_refuses_a_registration_without_a_last_name(): void
    {
        $this->assertRejects(['last_name' => ''], 'last_name');
    }

    #[Test]
    public function it_never_lets_the_status_be_mass_assigned(): void
    {
        $this->openEvent();

        $this->completeRegistration(self::EMAIL, ['status' => 'confirmed']);

        $this->assertSame(RegistrationStatus::Pending, Participant::query()->sole()->status);
    }

    #[Test]
    public function it_never_lets_the_registration_be_attached_to_another_account(): void
    {
        $this->openEvent();
        $other = $this->runner();

        $this->completeRegistration(self::EMAIL, ['user_id' => $other->id]);

        $this->assertSame(self::EMAIL, Participant::query()->sole()->user->email);
    }

    #[Test]
    public function it_lets_the_database_refuse_a_second_registration_for_one_account(): void
    {
        $event = $this->openEvent();
        $runner = $this->runner();
        Participant::factory()->create(['event_id' => $event->id, 'user_id' => $runner->id]);

        $this->expectException(QueryException::class);

        Participant::factory()->create(['event_id' => $event->id, 'user_id' => $runner->id]);
    }

    #[Test]
    public function it_shows_the_owner_their_registration_with_its_status(): void
    {
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
    public function it_lets_a_runner_correct_a_pending_registration(): void
    {
        $event = $this->openEvent();
        $runner = $this->runner();
        Participant::factory()->create(['event_id' => $event->id, 'user_id' => $runner->id]);

        $this->actingAs($runner)
            ->put(route('registration.update'), $this->registrationPayload(['phone' => '07 98 76 54 32']))
            ->assertRedirect(route('registration.show'));

        $this->assertSame('07 98 76 54 32', Participant::query()->sole()->phone);
    }

    #[Test]
    public function it_freezes_a_confirmed_registration_against_its_own_runner(): void
    {
        $event = $this->openEvent();
        $runner = $this->runner();
        Participant::factory()->confirmed()->create([
            'event_id' => $event->id,
            'user_id' => $runner->id,
            'phone' => '06 12 34 56 78',
        ]);

        $this->actingAs($runner)
            ->put(route('registration.update'), $this->registrationPayload(['phone' => '07 98 76 54 32']))
            ->assertForbidden();

        $this->assertSame('06 12 34 56 78', Participant::query()->sole()->phone);
    }

    #[Test]
    public function it_shows_a_cancelled_registration_in_read_only(): void
    {
        $event = $this->openEvent();
        $runner = $this->runner();
        Participant::factory()->cancelled()->create([
            'event_id' => $event->id,
            'user_id' => $runner->id,
        ]);

        $this->actingAs($runner)
            ->get(route('registration.show'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('registration/Show')
                ->where('registration.status', RegistrationStatus::Cancelled->value)
                ->where('canEdit', false));
    }

    #[Test]
    public function it_freezes_a_cancelled_registration_against_its_own_runner(): void
    {
        $event = $this->openEvent();
        $runner = $this->runner();
        Participant::factory()->cancelled()->create([
            'event_id' => $event->id,
            'user_id' => $runner->id,
            'phone' => '06 12 34 56 78',
        ]);

        $this->actingAs($runner)
            ->put(route('registration.update'), $this->registrationPayload(['phone' => '07 98 76 54 32']))
            ->assertForbidden();

        $this->assertSame('06 12 34 56 78', Participant::query()->sole()->phone);
    }

    #[Test]
    public function it_sends_a_runner_without_a_registration_to_the_dashboard(): void
    {
        $event = $this->openEvent();
        Participant::factory()->create(['event_id' => $event->id]);

        $this->actingAs($this->runner())
            ->get(route('registration.show'))
            ->assertRedirect(route('dashboard'));
    }

    #[Test]
    public function it_stops_the_manager_from_shrinking_the_event_below_the_confirmed_runners(): void
    {
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
        $this->openEvent();

        $this->completeRegistration(self::EMAIL, $overrides)
            ->assertSessionHasErrors($field);

        $this->assertDatabaseCount('users', 0);
    }

    private function runner(): User
    {
        return User::factory()->participant()->create();
    }
}
