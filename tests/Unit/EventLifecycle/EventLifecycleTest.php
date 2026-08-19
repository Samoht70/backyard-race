<?php

namespace Tests\Unit\EventLifecycle;

use App\Enums\EventStatus;
use App\Exceptions\EventTransitionRefusedException;
use App\Models\Event;
use App\Services\EventLifecycle\EventLifecycleFactory;
use App\Services\EventLifecycle\FinishedEventState;
use App\Services\EventLifecycle\RegistrationEventState;
use App\Services\EventLifecycle\RunningEventState;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EventLifecycleTest extends TestCase
{
    #[Test]
    public function it_moves_a_draft_event_to_registration(): void
    {
        $event = $this->event(EventStatus::Draft);

        $this->assertInstanceOf(
            RegistrationEventState::class,
            $event->lifecycle()->advance($event),
        );
    }

    #[Test]
    public function it_moves_a_registration_event_to_running(): void
    {
        $event = $this->event(EventStatus::Registration);

        $this->assertInstanceOf(
            RunningEventState::class,
            $event->lifecycle()->advance($event),
        );
    }

    #[Test]
    public function it_moves_a_running_event_to_finished(): void
    {
        $event = $this->event(EventStatus::Running);

        $this->assertInstanceOf(
            FinishedEventState::class,
            $event->lifecycle()->advance($event),
        );
    }

    #[Test]
    public function it_refuses_to_advance_a_finished_event(): void
    {
        $event = $this->event(EventStatus::Finished);

        $this->expectException(EventTransitionRefusedException::class);

        $event->lifecycle()->advance($event);
    }

    #[Test]
    public function it_offers_no_next_status_once_finished(): void
    {
        $this->assertNull($this->event(EventStatus::Finished)->lifecycle()->nextStatus());
    }

    #[Test]
    public function it_walks_the_chain_without_skipping_a_status(): void
    {
        $event = $this->event(EventStatus::Draft);

        $this->assertSame(EventStatus::Registration, $event->lifecycle()->nextStatus());
        $this->assertSame(
            EventStatus::Running,
            $this->event(EventStatus::Registration)->lifecycle()->nextStatus(),
        );
        $this->assertSame(
            EventStatus::Finished,
            $this->event(EventStatus::Running)->lifecycle()->nextStatus(),
        );
    }

    #[Test]
    public function it_refuses_to_start_a_race_without_a_first_start_time(): void
    {
        $event = $this->event(EventStatus::Registration, ['first_start_at' => null]);

        $this->assertSame(
            ['L’heure du premier départ n’est pas renseignée.'],
            $event->lifecycle()->refusals($event),
        );
    }

    #[Test]
    public function it_refuses_to_start_a_race_without_a_lap_distance(): void
    {
        $event = $this->event(EventStatus::Registration, ['lap_distance_meters' => null]);

        $this->assertSame(
            ['La distance d’une boucle n’est pas renseignée.'],
            $event->lifecycle()->refusals($event),
        );
    }

    #[Test]
    public function it_refuses_to_start_a_race_without_a_lap_duration(): void
    {
        $event = $this->event(EventStatus::Registration, ['lap_duration_minutes' => null]);

        $this->assertSame(
            ['La durée d’une boucle n’est pas renseignée.'],
            $event->lifecycle()->refusals($event),
        );
    }

    #[Test]
    public function it_names_every_missing_field_at_once(): void
    {
        $event = Event::factory()->registration()->incomplete()->make();

        $this->assertCount(3, $event->lifecycle()->refusals($event));
    }

    #[Test]
    public function it_throws_rather_than_starting_an_incomplete_race(): void
    {
        $event = Event::factory()->registration()->incomplete()->make();

        $this->expectException(EventTransitionRefusedException::class);

        $event->lifecycle()->advance($event);
    }

    #[Test]
    public function it_hides_a_draft_event_from_participants(): void
    {
        $this->assertFalse(
            $this->event(EventStatus::Draft)->lifecycle()->isVisibleToParticipants(),
        );

        foreach ([EventStatus::Registration, EventStatus::Running, EventStatus::Finished] as $status) {
            $this->assertTrue($this->event($status)->lifecycle()->isVisibleToParticipants());
        }
    }

    #[Test]
    public function it_opens_registrations_only_while_in_the_registration_status(): void
    {
        foreach (EventStatus::cases() as $status) {
            $this->assertSame(
                $status === EventStatus::Registration,
                $this->event($status)->lifecycle()->allowsRegistration(),
            );
        }
    }

    #[Test]
    public function it_freezes_the_first_start_time_and_the_lap_duration_once_running(): void
    {
        $this->assertSame(
            ['first_start_at', 'lap_duration_minutes'],
            $this->event(EventStatus::Running)->lifecycle()->frozenAttributes(),
        );
    }

    #[Test]
    public function it_leaves_everything_editable_before_the_race_starts(): void
    {
        foreach ([EventStatus::Draft, EventStatus::Registration] as $status) {
            $lifecycle = $this->event($status)->lifecycle();

            $this->assertTrue($lifecycle->isEditable());
            $this->assertSame([], $lifecycle->frozenAttributes());
        }
    }

    #[Test]
    public function it_freezes_every_fillable_attribute_once_finished(): void
    {
        $lifecycle = $this->event(EventStatus::Finished)->lifecycle();

        $this->assertFalse($lifecycle->isEditable());
        $this->assertSame(
            (new Event)->getFillable(),
            $lifecycle->frozenAttributes(),
        );
    }

    #[Test]
    public function it_builds_a_state_for_every_status(): void
    {
        $factory = new EventLifecycleFactory;

        foreach (EventStatus::cases() as $status) {
            $this->assertSame($status, $factory->fromStatus($status)->status());
        }
    }

    #[Test]
    public function it_only_races_while_the_event_is_running(): void
    {
        foreach (EventStatus::cases() as $status) {
            $this->assertSame(
                $status === EventStatus::Running,
                $this->event($status)->lifecycle()->isRacing(),
                $status->value,
            );
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function event(EventStatus $status, array $attributes = []): Event
    {
        return Event::factory()->make([...$attributes, 'status' => $status]);
    }
}
