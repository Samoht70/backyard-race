<?php

namespace Tests\Feature\Manage;

use App\Enums\RegistrationTransition;
use App\Models\Event;
use App\Models\Participant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BibNumberTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_assigns_the_first_number_to_the_first_confirmation(): void
    {
        $event = $this->openEvent();
        $participant = Participant::factory()->create(['event_id' => $event->id]);

        $this->confirm($participant);

        $this->assertSame(1, $participant->refresh()->bib_number);
    }

    #[Test]
    public function it_shows_the_first_number_on_three_digits(): void
    {
        $event = $this->openEvent();
        $participant = Participant::factory()->create(['event_id' => $event->id]);

        $this->confirm($participant);

        $this->actingAs($this->manager())
            ->get(route('manage.registrations.edit', $participant))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('registration.bib_label', '001'));
    }

    #[Test]
    public function it_assigns_the_next_number_after_the_highest_one(): void
    {
        $event = $this->openEvent();
        Participant::factory()->confirmed()->count(3)->create(['event_id' => $event->id]);
        $participant = Participant::factory()->create(['event_id' => $event->id]);

        $this->confirm($participant);

        $this->assertSame(4, $participant->refresh()->bib_number);
    }

    #[Test]
    public function it_never_reuses_the_number_of_a_cancelled_registration(): void
    {
        $event = $this->openEvent();
        $cancelled = Participant::factory()->confirmed()->withBib(2)->create(['event_id' => $event->id]);
        Participant::factory()->confirmed()->withBib(1)->create(['event_id' => $event->id]);
        Participant::factory()->confirmed()->withBib(3)->create(['event_id' => $event->id]);

        $this->transition($cancelled, RegistrationTransition::Cancel);

        $participant = Participant::factory()->create(['event_id' => $event->id]);
        $this->confirm($participant);

        $this->assertSame(4, $participant->refresh()->bib_number);
        $this->assertSame(2, $cancelled->refresh()->bib_number);
    }

    #[Test]
    public function it_keeps_the_number_when_a_registration_is_confirmed_again(): void
    {
        $event = $this->openEvent();
        Participant::factory()->confirmed()->count(6)->create(['event_id' => $event->id]);
        $participant = Participant::factory()->create(['event_id' => $event->id]);

        $this->confirm($participant);
        $this->assertSame(7, $participant->refresh()->bib_number);

        $this->transition($participant, RegistrationTransition::Cancel);
        $this->transition($participant, RegistrationTransition::Reopen);
        $this->confirm($participant);

        $this->assertSame(7, $participant->refresh()->bib_number);
    }

    #[Test]
    public function it_leaves_a_pending_registration_without_a_number(): void
    {
        $event = $this->openEvent();
        $participant = Participant::factory()->create(['event_id' => $event->id]);

        $this->assertNull($participant->bib_number);
    }

    #[Test]
    public function it_leaves_a_cancelled_registration_without_a_number(): void
    {
        $event = $this->openEvent();
        $participant = Participant::factory()->create(['event_id' => $event->id]);

        $this->transition($participant, RegistrationTransition::Cancel);

        $this->assertNull($participant->refresh()->bib_number);
    }

    #[Test]
    public function it_gives_two_successive_confirmations_two_distinct_numbers(): void
    {
        $event = $this->openEvent();
        $first = Participant::factory()->create(['event_id' => $event->id]);
        $second = Participant::factory()->create(['event_id' => $event->id]);

        $this->confirm($first);
        $this->confirm($second);

        $this->assertNotSame($first->refresh()->bib_number, $second->refresh()->bib_number);
    }

    /** Nothing but the unique index stops a write that bypasses the transition. */
    #[Test]
    public function it_lets_the_database_refuse_a_duplicate_number(): void
    {
        $event = $this->openEvent();
        $first = Participant::factory()->confirmed()->create(['event_id' => $event->id]);
        $second = Participant::factory()->create(['event_id' => $event->id]);

        $this->expectException(QueryException::class);

        $second->forceFill(['bib_number' => $first->bib_number])->save();
    }

    private function confirm(Participant $participant): void
    {
        $this->transition($participant, RegistrationTransition::Confirm);
    }

    private function transition(Participant $participant, RegistrationTransition $transition): void
    {
        $this->actingAs($this->manager())
            ->post(route('manage.registrations.transition', $participant), [
                'transition' => $transition->value,
            ])
            ->assertSessionHasNoErrors();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function openEvent(array $attributes = []): Event
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        return Event::factory()->registration()->create($attributes);
    }

    private function manager(): User
    {
        return User::factory()->manager()->create();
    }
}
