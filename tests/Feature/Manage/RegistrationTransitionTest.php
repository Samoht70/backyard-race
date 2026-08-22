<?php

namespace Tests\Feature\Manage;

use App\Actions\TransitionRegistration;
use App\Enums\Permission;
use App\Enums\RegistrationOutcome;
use App\Enums\RegistrationStatus;
use App\Enums\RegistrationTransition;
use App\Exceptions\RegistrationTransitionRefusedException;
use App\Models\Event;
use App\Models\Participant;
use App\Models\User;
use App\Notifications\RegistrationProcessed;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegistrationTransitionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    #[Test]
    public function it_confirms_a_pending_registration(): void
    {
        $participant = $this->registration(RegistrationStatus::Pending);

        $this->transition($participant, RegistrationTransition::Confirm)
            ->assertRedirect(route('manage.registrations.index'));

        $confirmed = $participant->refresh();

        $this->assertSame(RegistrationStatus::Confirmed, $confirmed->status);
        $this->assertNotNull($confirmed->bib_number);
    }

    #[Test]
    public function it_cancels_a_pending_registration(): void
    {
        $participant = $this->registration(RegistrationStatus::Pending);

        $this->transition($participant, RegistrationTransition::Cancel)
            ->assertRedirect(route('manage.registrations.index'));

        $this->assertSame(RegistrationStatus::Cancelled, $participant->refresh()->status);
    }

    #[Test]
    public function it_cancels_a_confirmed_registration_and_frees_the_seat(): void
    {
        $participant = $this->registration(RegistrationStatus::Confirmed);

        $this->assertSame(1, $participant->event->confirmedParticipantsCount());

        $bib = $participant->bib_number;

        $this->transition($participant, RegistrationTransition::Cancel);

        $cancelled = $participant->refresh();

        $this->assertSame(RegistrationStatus::Cancelled, $cancelled->status);
        $this->assertSame(0, $participant->event->confirmedParticipantsCount());
        $this->assertSame($bib, $cancelled->bib_number);
    }

    #[Test]
    public function it_reopens_a_cancelled_registration_without_losing_what_the_runner_typed(): void
    {
        $participant = $this->registration(RegistrationStatus::Cancelled, [
            'phone' => '06 11 22 33 44',
            'emergency_contact_name' => 'Camille Berger',
        ]);

        $this->transition($participant, RegistrationTransition::Reopen);

        $reopened = $participant->refresh();

        $this->assertSame(RegistrationStatus::Pending, $reopened->status);
        $this->assertSame('06 11 22 33 44', $reopened->phone);
        $this->assertSame('Camille Berger', $reopened->emergency_contact_name);
    }

    #[Test]
    public function it_refuses_to_confirm_a_cancelled_registration(): void
    {
        $participant = $this->registration(RegistrationStatus::Cancelled);

        $this->transition($participant, RegistrationTransition::Confirm)
            ->assertSessionHasErrors('transition');

        $this->assertSame(RegistrationStatus::Cancelled, $participant->refresh()->status);
    }

    #[Test]
    public function it_refuses_to_reopen_a_pending_registration(): void
    {
        $participant = $this->registration(RegistrationStatus::Pending);

        $this->transition($participant, RegistrationTransition::Reopen)
            ->assertSessionHasErrors('transition');

        $this->assertSame(RegistrationStatus::Pending, $participant->refresh()->status);
    }

    #[Test]
    public function it_refuses_to_confirm_once_the_event_is_full(): void
    {
        $event = $this->openEvent(['max_participants' => 1]);
        Participant::factory()->confirmed()->create(['event_id' => $event->id]);
        $participant = Participant::factory()->create(['event_id' => $event->id]);

        $this->transition($participant, RegistrationTransition::Confirm)
            ->assertSessionHasErrors('transition');

        $this->assertSame(RegistrationStatus::Pending, $participant->refresh()->status);
        Notification::assertNothingSent();
    }

    #[Test]
    public function it_confirms_without_a_cap_when_the_event_has_no_maximum(): void
    {
        $event = $this->openEvent(['max_participants' => null]);
        Participant::factory()->confirmed()->count(3)->create(['event_id' => $event->id]);
        $participant = Participant::factory()->create(['event_id' => $event->id]);

        $this->transition($participant, RegistrationTransition::Confirm)
            ->assertSessionHasNoErrors();

        $this->assertSame(RegistrationStatus::Confirmed, $participant->refresh()->status);
    }

    #[Test]
    public function it_confirms_a_late_arrival_while_the_race_is_running(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $event = Event::factory()->running()->create();
        $participant = Participant::factory()->create(['event_id' => $event->id]);

        $this->transition($participant, RegistrationTransition::Confirm)
            ->assertSessionHasNoErrors();

        $this->assertSame(RegistrationStatus::Confirmed, $participant->refresh()->status);
    }

    #[Test]
    public function it_changes_nothing_on_a_second_confirmation_click(): void
    {
        $participant = $this->registration(RegistrationStatus::Pending);

        $this->transition($participant, RegistrationTransition::Confirm);
        $bib = $participant->refresh()->bib_number;

        $this->transition($participant, RegistrationTransition::Confirm)
            ->assertSessionHasErrors('transition');

        $confirmed = $participant->refresh();

        $this->assertSame(RegistrationStatus::Confirmed, $confirmed->status);
        $this->assertSame($bib, $confirmed->bib_number);
        Notification::assertSentTimes(RegistrationProcessed::class, 1);
    }

    /** Between the form request's read and the write, another request can move the row. */
    #[Test]
    public function it_refuses_a_registration_someone_else_already_moved(): void
    {
        $participant = $this->registration(RegistrationStatus::Pending);

        Participant::query()
            ->whereKey($participant->getKey())
            ->update(['status' => RegistrationStatus::Confirmed->value]);

        $this->expectException(RegistrationTransitionRefusedException::class);

        app(TransitionRegistration::class)($participant, RegistrationTransition::Confirm);
    }

    #[Test]
    public function it_refuses_a_participant_confirming_his_own_registration(): void
    {
        $participant = $this->registration(RegistrationStatus::Pending);

        $this->actingAs($participant->user)
            ->post(route('manage.registrations.transition', $participant), [
                'transition' => RegistrationTransition::Confirm->value,
            ])
            ->assertForbidden();

        $this->assertSame(RegistrationStatus::Pending, $participant->refresh()->status);
    }

    #[Test]
    public function it_refuses_a_manager_who_only_carries_the_event_ability(): void
    {
        $participant = $this->registration(RegistrationStatus::Pending);

        $user = User::factory()->create();
        $user->givePermissionTo(Permission::ManageEvent->value);

        $this->actingAs($user)
            ->post(route('manage.registrations.transition', $participant), [
                'transition' => RegistrationTransition::Confirm->value,
            ])
            ->assertForbidden();
    }

    #[Test]
    public function it_admits_a_manager_carrying_only_the_participants_ability(): void
    {
        $participant = $this->registration(RegistrationStatus::Pending);

        $user = User::factory()->create();
        $user->givePermissionTo(Permission::ManageParticipants->value);

        $this->actingAs($user)
            ->post(route('manage.registrations.transition', $participant), [
                'transition' => RegistrationTransition::Confirm->value,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(RegistrationStatus::Confirmed, $participant->refresh()->status);
    }

    #[Test]
    public function it_refuses_an_unknown_transition(): void
    {
        $participant = $this->registration(RegistrationStatus::Pending);

        $this->actingAs($this->manager())
            ->post(route('manage.registrations.transition', $participant), ['transition' => 'archive'])
            ->assertSessionHasErrors('transition');

        $this->assertSame(RegistrationStatus::Pending, $participant->refresh()->status);
    }

    #[Test]
    public function it_flashes_the_new_status_to_the_manager(): void
    {
        $participant = $this->registration(RegistrationStatus::Pending);

        $this->transition($participant, RegistrationTransition::Confirm)
            ->assertSessionHas(
                'inertia.flash_data.toast.message',
                'L’inscription est passée en « Confirmée ».',
            );
    }

    #[Test]
    public function it_tells_the_runner_his_registration_is_validated(): void
    {
        $participant = $this->registration(RegistrationStatus::Pending);

        $this->transition($participant, RegistrationTransition::Confirm);

        $this->assertNotified($participant, RegistrationOutcome::Approved);
    }

    #[Test]
    public function it_tells_the_runner_his_registration_is_refused(): void
    {
        $participant = $this->registration(RegistrationStatus::Pending);

        $this->transition($participant, RegistrationTransition::Cancel);

        $this->assertNotified($participant, RegistrationOutcome::Refused);
    }

    #[Test]
    public function it_tells_the_runner_a_place_he_had_is_cancelled(): void
    {
        $participant = $this->registration(RegistrationStatus::Confirmed);

        $this->transition($participant, RegistrationTransition::Cancel);

        $this->assertNotified($participant, RegistrationOutcome::Cancelled);
    }

    #[Test]
    public function it_tells_the_runner_he_can_correct_his_registration_again(): void
    {
        $participant = $this->registration(RegistrationStatus::Cancelled);

        $this->transition($participant, RegistrationTransition::Reopen);

        $this->assertNotified($participant, RegistrationOutcome::Reopened);
    }

    private function assertNotified(Participant $participant, RegistrationOutcome $outcome): void
    {
        Notification::assertSentTo(
            $participant->user,
            RegistrationProcessed::class,
            fn (RegistrationProcessed $notification): bool => $notification->outcome === $outcome,
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function registration(RegistrationStatus $status, array $attributes = []): Participant
    {
        return Participant::factory()->create([
            'event_id' => $this->openEvent()->id,
            'status' => $status,
            ...$attributes,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function openEvent(array $attributes = []): Event
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        return Event::factory()->registration()->create($attributes);
    }

    private function transition(Participant $participant, RegistrationTransition $transition): TestResponse
    {
        return $this->actingAs($this->manager())
            ->post(route('manage.registrations.transition', $participant), [
                'transition' => $transition->value,
            ]);
    }

    private function manager(): User
    {
        return User::factory()->manager()->create();
    }
}
