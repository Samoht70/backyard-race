<?php

namespace Tests\Feature\Race;

use App\Actions\EliminateOverdueRunners;
use App\Actions\OpenDueRounds;
use App\Actions\ValidateLap;
use App\Enums\ExitReason;
use App\Enums\LapStatus;
use App\Enums\RunnerStatus;
use App\Models\Event;
use App\Models\Lap;
use App\Models\Participant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\RunsARace;
use Tests\TestCase;

class EliminateOverdueRunnersTest extends TestCase
{
    use RefreshDatabase;
    use RunsARace;

    #[Test]
    public function it_takes_out_the_runner_whose_lap_is_still_open_at_the_deadline(): void
    {
        $event = $this->runningEvent();
        $runner = $this->runner($event);
        $lap = Lap::factory()->for($this->roundOf($event))->for($runner)->create();
        $this->travelTo($this->at('2026-09-05 14:00'));

        $this->assertSame(1, $this->eliminate($event));

        $runner->refresh();
        $this->assertSame(LapStatus::Eliminated, $lap->refresh()->status);
        $this->assertSame(ExitReason::Timeout, $runner->exit_reason);
        $this->assertSame(RunnerStatus::Eliminated, $runner->runnerStatus());
        $this->assertSame('14:00:00', $runner->exited_at?->format('H:i:s'));
    }

    #[Test]
    public function it_leaves_the_runner_who_validated_on_the_last_second_in_the_race(): void
    {
        $event = $this->runningEvent();
        $runner = $this->runner($event);
        Lap::factory()
            ->validated($this->at('2026-09-05 13:59:59'))
            ->for($this->roundOf($event))
            ->for($runner)
            ->create();
        $this->travelTo($this->at('2026-09-05 14:00'));

        $this->assertSame(0, $this->eliminate($event));

        $this->assertTrue($runner->refresh()->isRunning());
    }

    #[Test]
    public function it_records_the_deadline_and_not_the_hour_it_runs(): void
    {
        $event = $this->runningEvent();
        $runner = $this->runner($event);
        Lap::factory()->for($this->roundOf($event))->for($runner)->create();
        $this->travelTo($this->at('2026-09-05 14:04'));

        $this->eliminate($event);

        $this->assertSame('14:00:00', $runner->refresh()->exited_at?->format('H:i:s'));
    }

    #[Test]
    public function it_eliminates_no_one_more_when_it_is_replayed(): void
    {
        $event = $this->runningEvent();
        $runner = $this->runner($event);
        Lap::factory()->for($this->roundOf($event))->for($runner)->create();
        $this->travelTo($this->at('2026-09-05 14:00'));
        $this->eliminate($event);
        $exitedAt = $runner->refresh()->exited_at;

        $this->travelTo($this->at('2026-09-05 14:30'));

        $this->assertSame(0, $this->eliminate($event));
        $this->assertEquals($exitedAt, $runner->refresh()->exited_at);
    }

    #[Test]
    public function it_takes_the_runner_out_on_the_first_deadline_they_missed(): void
    {
        $event = $this->runningEvent();
        $runner = $this->runner($event);
        Lap::factory()->for($this->roundOf($event, 1))->for($runner)->create();
        Lap::factory()->for($this->roundOf($event, 2))->for($runner)->create();
        Lap::factory()->for($this->roundOf($event, 3))->for($runner)->create();
        $this->travelTo($this->at('2026-09-05 16:30'));

        $this->assertSame(1, $this->eliminate($event));

        $this->assertSame('14:00:00', $runner->refresh()->exited_at?->format('H:i:s'));
        $this->assertSame(3, $runner->laps()->where('status', LapStatus::Eliminated)->count());
    }

    #[Test]
    public function it_keeps_the_reason_of_a_runner_who_had_already_stopped(): void
    {
        $event = $this->runningEvent();
        $runner = Participant::factory()
            ->confirmed()
            ->outOfTheRace(ExitReason::Withdrawal)
            ->create(['event_id' => $event->getKey()]);
        Lap::factory()->for($this->roundOf($event))->for($runner)->create();
        $this->travelTo($this->at('2026-09-05 14:00'));

        $this->assertSame(0, $this->eliminate($event));

        $this->assertSame(ExitReason::Withdrawal, $runner->refresh()->exit_reason);
    }

    #[Test]
    public function it_eliminates_nobody_before_the_deadline_of_the_round(): void
    {
        $event = $this->runningEvent();
        $runner = $this->runner($event);
        Lap::factory()->for($this->roundOf($event))->for($runner)->create();
        $this->travelTo($this->at('2026-09-05 13:59:59'));

        $this->assertSame(0, $this->eliminate($event));

        $this->assertTrue($runner->refresh()->isRunning());
    }

    #[Test]
    public function it_eliminates_nobody_while_the_event_is_not_running(): void
    {
        $event = Event::factory()->finished()->create([
            'first_start_at' => $this->at('2026-09-05 13:00'),
            'lap_duration_minutes' => 60,
        ]);
        $runner = $this->runner($event);
        Lap::factory()->for($this->roundOf($event))->for($runner)->create();
        $this->travelTo($this->at('2026-09-05 17:30'));

        $this->assertSame(0, $this->eliminate($event));

        $this->assertTrue($runner->refresh()->isRunning());
    }

    #[Test]
    public function it_catches_up_every_late_round_with_its_own_deadline(): void
    {
        $this->travelTo($this->at('2026-09-05 13:30'));
        $event = $this->runningEvent();
        $runners = $this->runners($event, 3);
        app(OpenDueRounds::class)($event);
        app(ValidateLap::class)($this->lapOf($runners[0], 1));

        $this->travelTo($this->at('2026-09-05 15:10'));
        app(OpenDueRounds::class)($event);

        $this->assertSame(3, $this->eliminate($event));
        $this->assertSame('14:00:00', $runners[1]->refresh()->exited_at?->format('H:i:s'));
        $this->assertSame('14:00:00', $runners[2]->refresh()->exited_at?->format('H:i:s'));
        $this->assertSame('15:00:00', $runners[0]->refresh()->exited_at?->format('H:i:s'));
    }

    #[Test]
    public function it_opens_no_further_round_once_the_race_has_lost_every_runner(): void
    {
        $event = $this->runningEvent();
        $runner = $this->runner($event);
        Lap::factory()->for($this->roundOf($event))->for($runner)->create();
        $this->travelTo($this->at('2026-09-05 14:00'));
        $this->eliminate($event);

        $this->travelTo($this->at('2026-09-05 17:30'));

        $this->assertSame([], app(OpenDueRounds::class)($event));
        $this->assertDatabaseCount('rounds', 1);
    }

    private function eliminate(Event $event): int
    {
        return app(EliminateOverdueRunners::class)($event);
    }

    private function lapOf(Participant $runner, int $roundNumber): Lap
    {
        return Lap::query()
            ->where('participant_id', $runner->getKey())
            ->whereRelation('round', 'number', $roundNumber)
            ->sole();
    }
}
