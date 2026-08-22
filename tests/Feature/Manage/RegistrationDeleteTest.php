<?php

namespace Tests\Feature\Manage;

use App\Enums\Permission;
use App\Enums\RegistrationStatus;
use App\Enums\RegistrationTransition;
use App\Enums\Role;
use App\Models\Event;
use App\Models\Participant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\RegistersRunners;
use Tests\TestCase;

class RegistrationDeleteTest extends TestCase
{
    use RefreshDatabase, RegistersRunners;

    #[Test]
    public function it_deletes_a_pending_registration_with_its_account_its_roles_and_its_sessions(): void
    {
        $participant = $this->registration(RegistrationStatus::Pending);
        $runner = $participant->user;
        $runner->assignRole(Role::Participant);
        $this->openSession('runner-session', $runner);

        $this->deleteRegistration($participant)
            ->assertRedirect(route('manage.registrations.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('participants', ['id' => $participant->getKey()]);
        $this->assertDatabaseMissing('users', ['id' => $runner->getKey()]);
        $this->assertDatabaseMissing('sessions', ['id' => 'runner-session']);
        $this->assertDatabaseCount('model_has_roles', 1);
    }

    #[Test]
    public function it_frees_a_seat_without_handing_the_bib_number_back(): void
    {
        $event = $this->openEventWithRoles(['max_participants' => 1]);
        $confirmed = Participant::factory()->confirmed()->create([
            'event_id' => $event->id,
            'bib_number' => 3,
        ]);
        $waiting = Participant::factory()->create(['event_id' => $event->id]);

        $this->assertTrue($event->refresh()->isFull());

        $this->deleteRegistration($confirmed)->assertSessionHasNoErrors();

        $this->assertFalse($event->refresh()->isFull());

        $this->transition($waiting, RegistrationTransition::Confirm)->assertSessionHasNoErrors();

        $this->assertSame(RegistrationStatus::Confirmed, $waiting->refresh()->status);
        $this->assertNotSame(3, $waiting->bib_number);
    }

    #[Test]
    public function it_leaves_the_address_free_for_a_brand_new_registration(): void
    {
        $event = $this->openEventWithRoles();
        $participant = Participant::factory()->create([
            'event_id' => $event->id,
            'user_id' => User::factory()->create(['email' => 'perdu@backyard.test']),
        ]);

        $this->deleteRegistration($participant)->assertSessionHasNoErrors();

        Auth::logout();
        $this->flushSession();

        $this->completeRegistration('perdu@backyard.test')
            ->assertRedirect(route('account.show'));

        $this->assertSame('perdu@backyard.test', Participant::query()->sole()->user->email);
    }

    #[Test]
    public function it_takes_the_registration_of_the_organiser_and_leaves_the_account(): void
    {
        $event = $this->openEventWithRoles();
        $organiser = User::factory()->manager()->create();
        $participant = Participant::factory()->create([
            'event_id' => $event->id,
            'user_id' => $organiser,
        ]);

        $this->deleteRegistration($participant)->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('participants', ['id' => $participant->getKey()]);
        $this->assertSame([Role::Manager->value], $organiser->fresh()?->getRoleNames()->all());
    }

    #[Test]
    public function it_spares_the_configured_address_even_when_it_carries_no_role(): void
    {
        config()->set('race.organiser_email', 'ORGA@Backyard.Test ');
        $event = $this->openEventWithRoles();
        $organiser = User::factory()->create(['email' => 'orga@backyard.test']);
        $participant = Participant::factory()->create([
            'event_id' => $event->id,
            'user_id' => $organiser,
        ]);

        $this->deleteRegistration($participant)->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('participants', ['id' => $participant->getKey()]);
        $this->assertDatabaseHas('users', ['id' => $organiser->getKey()]);
    }

    #[Test]
    public function it_refuses_while_the_race_is_running_and_names_the_status(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $event = Event::factory()->running()->create();
        $participant = Participant::factory()->create(['event_id' => $event->id]);

        $this->deleteRegistration($participant)
            ->assertSessionHasErrors(['registration' => 'L’événement est « Course en cours » : une inscription n’est plus une ligne de formulaire, c’est un coureur sur le terrain.']);

        $this->assertDatabaseHas('participants', ['id' => $participant->getKey()]);
        $this->assertDatabaseHas('users', ['id' => $participant->user_id]);
    }

    #[Test]
    public function it_refuses_a_participant_deleting_his_own_registration(): void
    {
        $participant = $this->registration(RegistrationStatus::Pending);

        $this->actingAs($participant->user)
            ->delete(route('manage.registrations.destroy', $participant))
            ->assertForbidden();

        $this->assertDatabaseHas('participants', ['id' => $participant->getKey()]);
    }

    #[Test]
    public function it_refuses_a_manager_who_only_carries_the_event_ability(): void
    {
        $participant = $this->registration(RegistrationStatus::Pending);
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::ManageEvent->value);

        $this->actingAs($user)
            ->delete(route('manage.registrations.destroy', $participant))
            ->assertForbidden();

        $this->assertDatabaseHas('participants', ['id' => $participant->getKey()]);
    }

    #[Test]
    public function it_deletes_a_cancelled_registration_too(): void
    {
        $participant = $this->registration(RegistrationStatus::Cancelled);

        $this->deleteRegistration($participant)->assertSessionHasNoErrors();

        $this->assertDatabaseCount('participants', 0);
    }

    #[Test]
    public function it_answers_a_registration_a_second_tab_already_deleted(): void
    {
        $participant = $this->registration(RegistrationStatus::Pending);
        $route = route('manage.registrations.destroy', $participant);

        $this->deleteRegistration($participant)->assertSessionHasNoErrors();

        $this->actingAs($this->manager())->delete($route)->assertNotFound();
    }

    #[Test]
    public function it_flashes_the_runner_it_deleted(): void
    {
        $participant = $this->registration(RegistrationStatus::Pending);
        $participant->user->update(['first_name' => 'Nouvelle', 'last_name' => 'Recrue']);

        $this->deleteRegistration($participant)->assertSessionHas(
            'inertia.flash_data.toast.message',
            'L’inscription de Nouvelle Recrue et son compte sont supprimés.',
        );
    }

    private function deleteRegistration(Participant $participant): TestResponse
    {
        return $this->actingAs($this->manager())
            ->delete(route('manage.registrations.destroy', $participant));
    }

    private function transition(Participant $participant, RegistrationTransition $transition): TestResponse
    {
        return $this->actingAs($this->manager())
            ->post(route('manage.registrations.transition', $participant), [
                'transition' => $transition->value,
            ]);
    }

    private function registration(RegistrationStatus $status): Participant
    {
        return Participant::factory()->create([
            'event_id' => $this->openEventWithRoles()->id,
            'status' => $status,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function openEventWithRoles(array $attributes = []): Event
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        return $this->openEvent($attributes);
    }

    private function manager(): User
    {
        return User::factory()->manager()->create();
    }

    private function openSession(string $id, User $account): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $account->getKey(),
            'payload' => '',
            'last_activity' => 0,
        ]);
    }
}
