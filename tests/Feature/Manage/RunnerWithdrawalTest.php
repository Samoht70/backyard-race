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

class RunnerWithdrawalTest extends TestCase
{
    use RefreshDatabase;
    use RunsARace;

    #[Test]
    public function it_takes_out_the_runner_the_manager_confirmed(): void
    {
        $event = $this->racingEvent();
        $runner = $this->runner($event);
        $lap = $this->roundOf($event)->laps()->create(['participant_id' => $runner->id]);
        $this->travelTo($this->at('2026-09-05 13:42:10'));

        $response = $this->post(route('manage.runners.withdraw', $runner));

        $response->assertRedirect(route('manage.index'));
        $this->assertSame(ExitReason::Withdrawal, $runner->refresh()->exit_reason);
        $this->assertSame('13:42:10', $runner->exited_at?->format('H:i:s'));
        $this->assertSame(LapStatus::Eliminated, $lap->refresh()->status);
    }

    #[Test]
    public function it_refuses_a_confirmation_sent_a_second_time(): void
    {
        $runner = $this->runner($this->racingEvent());
        $this->post(route('manage.runners.withdraw', $runner));

        $response = $this->post(route('manage.runners.withdraw', $runner));

        $response->assertSessionHasErrors([
            'runner' => 'Ce coureur est déjà sorti de la course : son abandon ne s’enregistre pas une seconde fois.',
        ]);
    }

    #[Test]
    public function it_refuses_a_runner_declaring_their_own_withdrawal(): void
    {
        $runner = $this->runner($this->racingEvent());

        $this->actingAs($runner->user)
            ->post(route('manage.runners.withdraw', $runner))
            ->assertForbidden();

        $this->assertNull($runner->refresh()->exited_at);
    }

    #[Test]
    public function it_shows_the_laps_and_the_distance_the_confirmation_needs(): void
    {
        $event = $this->racingEvent();
        $runner = $this->runner($event);

        foreach (range(1, 7) as $number) {
            Lap::factory()->validated()->for($this->roundOf($event, $number))->for($runner)->create();
        }

        $this->roundOf($event, 8)->laps()->create(['participant_id' => $runner->id]);
        $this->travelTo($this->at('2026-09-05 20:30'));

        $this->get(route('manage.index'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('roundRunners.0.runner_id', $runner->id)
            ->where('roundRunners.0.validated_laps', 7)
            ->where('roundRunners.0.covered_meters', 42000)
            ->where('roundRunners.0.status', RunnerStatus::Running->value)
            ->etc());
    }

    #[Test]
    public function it_moves_the_withdrawn_runner_from_the_board_to_the_tally(): void
    {
        $event = $this->racingEvent();
        $runner = $this->runner($event);
        $this->roundOf($event)->laps()->create(['participant_id' => $runner->id]);
        $this->post(route('manage.runners.withdraw', $runner));

        $this->travelTo($this->at('2026-09-05 13:50'));

        $this->get(route('manage.index'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('roundRunners', [])
            ->where('tally.running', 0)
            ->where('tally.out', 1)
            ->etc());
    }

    #[Test]
    public function it_leaves_the_board_empty_of_a_runner_who_stopped_on_the_previous_round(): void
    {
        $event = $this->racingEvent();
        $runner = $this->runner($event);
        $this->roundOf($event)->laps()->create(['participant_id' => $runner->id]);
        $this->post(route('manage.runners.withdraw', $runner));

        $this->roundOf($event, 2);
        $this->travelTo($this->at('2026-09-05 14:30'));

        $this->get(route('manage.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('roundRunners', [])->etc());
    }

    #[Test]
    public function it_refuses_a_withdrawal_once_the_event_is_finished(): void
    {
        $event = Event::factory()->finished()->create([
            'first_start_at' => $this->at('2026-09-05 13:00'),
            'lap_duration_minutes' => 60,
        ]);
        $runner = Participant::factory()->confirmed()->create(['event_id' => $event->getKey()]);

        $this->post(route('manage.runners.withdraw', $runner))->assertForbidden();

        $this->assertNull($runner->refresh()->exited_at);
    }

    private function racingEvent(): Event
    {
        return Event::factory()->running()->create([
            'first_start_at' => $this->at('2026-09-05 13:00'),
            'lap_duration_minutes' => 60,
            'lap_distance_meters' => 6000,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->actingAs(User::factory()->manager()->create());
    }
}
