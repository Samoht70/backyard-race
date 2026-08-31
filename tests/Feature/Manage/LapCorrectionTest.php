<?php

namespace Tests\Feature\Manage;

use App\Enums\ExitReason;
use App\Enums\LapStatus;
use App\Enums\RunnerStatus;
use App\Models\Event;
use App\Models\Lap;
use App\Models\Participant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\RunsARace;
use Tests\TestCase;

class LapCorrectionTest extends TestCase
{
    use RefreshDatabase;
    use RunsARace;

    #[Test]
    public function it_lists_the_laps_to_catch_up_and_the_validations_to_undo(): void
    {
        $event = $this->racingEvent();
        $missed = $this->missedLap($event);
        $validated = Lap::factory()
            ->validated($this->at('2026-09-05 17:42'))
            ->for($this->roundOf($event, 2))
            ->for($this->runner($event))
            ->create();
        $this->travelTo($this->at('2026-09-05 18:05'));

        $this->get(route('manage.corrections'))->assertInertia(fn (AssertableInertia $page) => $page
            ->component('manage/Corrections')
            ->where('reinstatable.0.lap_id', $missed->id)
            ->where('reinstatable.0.lap_status', LapStatus::Eliminated->value)
            ->where('reinstatable.0.round_deadline_at', '18:00')
            ->where('reinstatable.0.status', RunnerStatus::Eliminated->value)
            ->where('revertable.0.lap_id', $validated->id)
            ->where('revertable.0.validated_at', '17:42')
            ->etc());
    }

    #[Test]
    public function it_offers_one_line_per_runner_on_the_first_deadline_they_missed(): void
    {
        $event = $this->racingEvent();
        $runner = $this->runner($event);

        foreach (range(1, 3) as $number) {
            $this->roundOf($event, $number)->laps()->create(['participant_id' => $runner->id]);
        }

        $runner->leaveRace(ExitReason::Timeout, $this->at('2026-09-05 18:00'));
        $this->travelTo($this->at('2026-09-05 20:05'));

        $this->get(route('manage.corrections'))->assertInertia(fn (AssertableInertia $page) => $page
            ->count('reinstatable', 1)
            ->where('reinstatable.0.round_number', 1)
            ->where('reinstatable.0.round_starts_at', '17:00')
            ->where('reinstatable.0.round_deadline_at', '18:00')
            ->etc());
    }

    #[Test]
    public function it_puts_the_runner_back_in_the_race_on_the_time_the_manager_typed(): void
    {
        $event = $this->racingEvent();
        $lap = $this->missedLap($event);
        $this->travelTo($this->at('2026-09-05 18:05'));

        $response = $this->post(route('manage.laps.reinstate', $lap), ['finished_at' => '17:58']);

        $response->assertRedirect(route('manage.corrections'));
        $this->assertSame(LapStatus::Validated, $lap->refresh()->status);
        $this->assertSame('17:58:00', $lap->validated_at?->format('H:i:s'));
        $this->assertTrue($lap->participant->refresh()->isRunning());
    }

    #[Test]
    public function it_refuses_a_finish_time_before_the_round_started(): void
    {
        $event = $this->racingEvent();
        $lap = $this->missedLap($event);
        $this->travelTo($this->at('2026-09-05 18:05'));

        $response = $this->post(route('manage.laps.reinstate', $lap), ['finished_at' => '16:30']);

        $response->assertSessionHasErrors([
            'finished_at' => 'L’heure indiquée précède le départ du tour : ce n’est pas une heure d’arrivée possible.',
        ]);
        $this->assertSame(LapStatus::Eliminated, $lap->refresh()->status);
    }

    #[Test]
    public function it_refuses_a_reinstatement_without_a_finish_time(): void
    {
        $event = $this->racingEvent();
        $lap = $this->missedLap($event);

        $this->post(route('manage.laps.reinstate', $lap))->assertSessionHasErrors('finished_at');

        $this->assertSame(LapStatus::Eliminated, $lap->refresh()->status);
    }

    #[Test]
    public function it_sends_the_lap_back_to_the_queue_while_the_round_is_open(): void
    {
        $event = $this->racingEvent();
        $lap = Lap::factory()
            ->validated($this->at('2026-09-05 17:20'))
            ->for($this->roundOf($event))
            ->for($this->runner($event))
            ->create();
        $this->travelTo($this->at('2026-09-05 17:30'));

        $this->post(route('manage.laps.revert', $lap))->assertRedirect(route('manage.corrections'));

        $this->assertSame(LapStatus::Pending, $lap->refresh()->status);
        $this->assertTrue($lap->participant->refresh()->isRunning());
    }

    #[Test]
    public function it_takes_the_runner_out_when_the_undone_round_is_over(): void
    {
        $event = $this->racingEvent();
        $lap = Lap::factory()
            ->validated($this->at('2026-09-05 17:20'))
            ->for($this->roundOf($event))
            ->for($this->runner($event))
            ->create();
        $this->travelTo($this->at('2026-09-05 18:05'));

        $this->post(route('manage.laps.revert', $lap));

        $this->assertSame(LapStatus::Eliminated, $lap->refresh()->status);
        $this->assertSame(ExitReason::Timeout, $lap->participant->refresh()->exit_reason);
    }

    #[Test]
    public function it_refuses_to_undo_a_lap_that_was_never_validated(): void
    {
        $event = $this->racingEvent();
        $lap = $this->missedLap($event);

        $this->post(route('manage.laps.revert', $lap))->assertSessionHasErrors([
            'lap' => 'Cette boucle n’est pas validée : il n’y a aucune validation à annuler.',
        ]);
    }

    #[Test]
    public function it_shows_the_correction_marker_on_the_board(): void
    {
        $event = $this->racingEvent();
        $lap = $this->missedLap($event);
        $this->travelTo($this->at('2026-09-05 17:50'));
        $this->post(route('manage.laps.reinstate', $lap), ['finished_at' => '17:45']);

        $this->get(route('manage.index'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('roundRunners.0.corrected', true)
            ->where('roundRunners.0.status', RunnerStatus::Running->value)
            ->etc());
    }

    #[Test]
    public function it_refuses_every_correction_once_the_event_is_finished(): void
    {
        $event = Event::factory()->finished()->create([
            'first_start_at' => $this->at('2026-09-05 17:00'),
            'lap_duration_minutes' => 60,
        ]);
        $lap = Lap::factory()
            ->eliminated()
            ->for($this->roundOf($event))
            ->for(Participant::factory()->confirmed()->state(['event_id' => $event->getKey()]))
            ->create();

        $this->get(route('manage.corrections'))->assertForbidden();
        $this->post(route('manage.laps.reinstate', $lap), ['finished_at' => '17:58'])->assertForbidden();
        $this->post(route('manage.laps.revert', $lap))->assertForbidden();

        $this->assertSame(LapStatus::Eliminated, $lap->refresh()->status);
    }

    #[Test]
    public function it_refuses_a_runner_reaching_the_correction_desk(): void
    {
        $event = $this->racingEvent();
        $lap = $this->missedLap($event);

        $this->actingAs($lap->participant->user)
            ->get(route('manage.corrections'))
            ->assertForbidden();

        $this->actingAs($lap->participant->user)
            ->post(route('manage.laps.reinstate', $lap), ['finished_at' => '17:58'])
            ->assertForbidden();

        $this->assertSame(LapStatus::Eliminated, $lap->refresh()->status);
    }

    private function racingEvent(): Event
    {
        return Event::factory()->running()->create([
            'first_start_at' => $this->at('2026-09-05 17:00'),
            'lap_duration_minutes' => 60,
            'lap_distance_meters' => 6000,
        ]);
    }

    private function missedLap(Event $event): Lap
    {
        $runner = $this->runner($event);
        $lap = $this->roundOf($event)->laps()->create(['participant_id' => $runner->id]);

        $runner->leaveRace(ExitReason::Timeout, $this->at('2026-09-05 18:00'));

        return $lap->refresh();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->actingAs(User::factory()->manager()->create());
    }
}
