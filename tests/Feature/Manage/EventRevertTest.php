<?php

namespace Tests\Feature\Manage;

use App\Actions\RevertEventStatus;
use App\Enums\EventStatus;
use App\Exceptions\EventTransitionRefusedException;
use App\Models\Document;
use App\Models\Event;
use App\Models\Participant;
use App\Models\User;
use App\Services\EventLifecycle\DraftEventState;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EventRevertTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    #[Test]
    public function it_closes_an_untouched_event_back_into_a_draft(): void
    {
        Event::factory()->registration()->create();

        $this->actingAs($this->manager())
            ->post(route('manage.event.revert'), ['to' => EventStatus::Draft->value])
            ->assertRedirect(route('manage.event.edit'));

        $this->assertSame(EventStatus::Draft, Event::query()->sole()->status);
    }

    #[Test]
    public function it_hands_the_draft_state_back_from_the_open_registrations(): void
    {
        $event = Event::factory()->registration()->create();

        $this->assertInstanceOf(
            DraftEventState::class,
            $event->lifecycle()->revert($event),
        );
    }

    #[Test]
    public function it_keeps_the_briefing_the_documents_and_the_schedule(): void
    {
        $event = Event::factory()->registration()->create(['briefing' => 'Rendez-vous à 12h30.']);
        Document::factory()->count(2)->for($event)->create();

        $this->actingAs($this->manager())
            ->post(route('manage.event.revert'), ['to' => EventStatus::Draft->value]);

        $reverted = Event::query()->sole();

        $this->assertSame('Rendez-vous à 12h30.', $reverted->briefing);
        $this->assertSame(2, $reverted->documents()->count());
        $this->assertTrue($event->first_start_at->equalTo($reverted->first_start_at));
        $this->assertSame($event->lap_distance_meters, $reverted->lap_distance_meters);
        $this->assertSame($event->lap_duration_minutes, $reverted->lap_duration_minutes);
    }

    #[Test]
    public function it_hides_the_event_and_closes_the_public_registration_path(): void
    {
        Event::factory()->registration()->create();

        $this->actingAs($this->manager())
            ->post(route('manage.event.revert'), ['to' => EventStatus::Draft->value]);

        $this->assertFalse(Event::query()->sole()->lifecycle()->isVisibleToParticipants());

        Auth::logout();

        $this->get(route('account.create'))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('auth/register/Start')
                    ->where('open', false),
            );

        $this->post(route('account.store'), ['email' => 'recrue@backyard.test'])
            ->assertSessionHasErrors('event');
    }

    #[Test]
    public function it_counts_the_cancelled_registrations_that_block_the_return(): void
    {
        $event = Event::factory()->registration()->create();
        Participant::factory()->count(2)->cancelled()->for($event)->create();

        $this->actingAs($this->manager())
            ->post(route('manage.event.revert'), ['to' => EventStatus::Draft->value])
            ->assertSessionHasErrors([
                'to' => '2 inscriptions existent encore : supprime-les avant de refermer l’événement.',
            ]);

        $this->assertSame(EventStatus::Registration, Event::query()->sole()->status);
    }

    #[Test]
    public function it_names_the_single_registration_that_blocks_the_return(): void
    {
        $event = Event::factory()->registration()->create();
        Participant::factory()->confirmed()->for($event)->create();

        $this->actingAs($this->manager())
            ->post(route('manage.event.revert'), ['to' => EventStatus::Draft->value])
            ->assertSessionHasErrors([
                'to' => 'Une inscription existe encore : supprime-la avant de refermer l’événement.',
            ]);
    }

    #[Test]
    public function it_refuses_to_reopen_a_running_event(): void
    {
        $this->assertRefuses(Event::factory()->running()->create());
    }

    #[Test]
    public function it_refuses_to_reopen_an_event_that_is_already_a_draft(): void
    {
        $this->assertRefuses(Event::factory()->create());
    }

    #[Test]
    public function it_refuses_to_reopen_a_finished_event(): void
    {
        $this->assertRefuses(Event::factory()->finished()->create());
    }

    #[Test]
    public function it_refuses_a_participant(): void
    {
        Event::factory()->registration()->create();

        $this->actingAs(User::factory()->participant()->create())
            ->post(route('manage.event.revert'), ['to' => EventStatus::Draft->value])
            ->assertForbidden();

        $this->assertSame(EventStatus::Registration, Event::query()->sole()->status);
    }

    #[Test]
    public function it_refuses_an_event_someone_else_already_started(): void
    {
        $event = Event::factory()->registration()->create();

        Event::query()->whereKey($event->getKey())
            ->update(['status' => EventStatus::Running->value]);

        $this->expectException(EventTransitionRefusedException::class);

        app(RevertEventStatus::class)($event, EventStatus::Draft);
    }

    #[Test]
    public function it_refuses_a_registration_that_landed_after_the_screen_was_drawn(): void
    {
        $event = Event::factory()->registration()->create();
        Participant::factory()->for($event)->create();

        $this->expectException(EventTransitionRefusedException::class);

        app(RevertEventStatus::class)($event, EventStatus::Draft);
    }

    #[Test]
    public function it_offers_the_return_only_while_the_registrations_are_open(): void
    {
        Event::factory()->registration()->create();

        $this->actingAs($this->manager())
            ->get(route('manage.event.edit'))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('transition.previous', EventStatus::Draft->value)
                    ->where('transition.revertRefusals', [])
                    ->where('transition.nextIsReversible', false),
            );
    }

    #[Test]
    public function it_announces_the_first_step_as_reversible_while_the_event_is_a_draft(): void
    {
        Event::factory()->create();

        $this->actingAs($this->manager())
            ->get(route('manage.event.edit'))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('transition.previous', null)
                    ->where('transition.nextIsReversible', true),
            );
    }

    #[Test]
    public function it_flashes_the_status_the_event_returned_to(): void
    {
        Event::factory()->registration()->create();

        $this->actingAs($this->manager())
            ->post(route('manage.event.revert'), ['to' => EventStatus::Draft->value])
            ->assertSessionHas(
                'inertia.flash_data.toast.message',
                'L’événement est revenu en « Brouillon ».',
            );
    }

    private function assertRefuses(Event $event): void
    {
        $this->actingAs($this->manager())
            ->post(route('manage.event.revert'), ['to' => EventStatus::Draft->value])
            ->assertForbidden();

        $this->assertSame($event->status, Event::query()->sole()->status);
    }

    private function manager(): User
    {
        return User::factory()->manager()->create();
    }
}
