<?php

namespace Tests\Feature\Race;

use App\Actions\OpenRoundLaps;
use App\Actions\WithdrawRunner;
use App\Enums\ExitReason;
use App\Enums\LapStatus;
use App\Enums\RunnerStatus;
use App\Exceptions\RunnerExitRefusedException;
use App\Models\Event;
use App\Models\Lap;
use App\Models\Participant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\RunsARace;
use Tests\TestCase;

class WithdrawRunnerTest extends TestCase
{
    use RefreshDatabase;
    use RunsARace;

    #[Test]
    public function it_records_the_reason_and_the_time_the_runner_stopped(): void
    {
        $event = $this->runningEvent();
        $runner = $this->runner($event);
        $lap = Lap::factory()->for($this->roundOf($event))->for($runner)->create();
        $this->travelTo($this->at('2026-09-05 13:42:10'));

        app(WithdrawRunner::class)($runner);

        $runner->refresh();
        $this->assertSame(ExitReason::Withdrawal, $runner->exit_reason);
        $this->assertSame('13:42:10', $runner->exited_at?->format('H:i:s'));
        $this->assertSame(RunnerStatus::Withdrawn, $runner->runnerStatus());
        $this->assertSame(LapStatus::Eliminated, $lap->refresh()->status);
    }

    #[Test]
    public function it_keeps_the_laps_the_runner_had_already_validated(): void
    {
        $event = $this->runningEvent();
        $runner = $this->runner($event);
        $this->lapsValidated($event, $runner, 7);
        $running = Lap::factory()->for($this->roundOf($event, 8))->for($runner)->create();

        app(WithdrawRunner::class)($runner);

        $this->assertSame(7, $runner->refresh()->validatedLapsCount());
        $this->assertSame(LapStatus::Eliminated, $running->refresh()->status);
    }

    #[Test]
    public function it_takes_the_runner_out_after_their_lap_was_validated(): void
    {
        $event = $this->runningEvent();
        $runner = $this->runner($event);
        $lap = Lap::factory()->validated()->for($this->roundOf($event))->for($runner)->create();

        app(WithdrawRunner::class)($runner);

        $this->assertFalse($runner->refresh()->isRunning());
        $this->assertSame(LapStatus::Validated, $lap->refresh()->status);
    }

    #[Test]
    public function it_opens_no_lap_for_a_runner_who_stopped(): void
    {
        $event = $this->runningEvent();
        $stopped = $this->runner($event);
        $running = $this->runner($event);
        app(WithdrawRunner::class)($stopped);

        $opened = app(OpenRoundLaps::class)($this->roundOf($event, 2));

        $this->assertSame(1, $opened);
        $this->assertSame([$running->getKey()], Participant::query()->running()->pluck('id')->all());
        $this->assertSame(0, $stopped->laps()->count());
    }

    #[Test]
    public function it_refuses_a_runner_who_already_stopped(): void
    {
        $runner = $this->runner($this->runningEvent());
        app(WithdrawRunner::class)($runner);

        $this->expectException(RunnerExitRefusedException::class);

        app(WithdrawRunner::class)($runner->refresh());
    }

    #[Test]
    public function it_keeps_the_first_exit_time_when_the_confirmation_is_sent_twice(): void
    {
        $runner = $this->runner($this->runningEvent());
        $this->travelTo($this->at('2026-09-05 13:42:10'));
        app(WithdrawRunner::class)($runner);

        $this->travelTo($this->at('2026-09-05 14:10:00'));
        rescue(fn () => app(WithdrawRunner::class)($runner->refresh()), report: false);

        $this->assertSame('13:42:10', $runner->refresh()->exited_at?->format('H:i:s'));
    }

    #[Test]
    public function it_keeps_the_reason_of_a_runner_the_clock_already_caught(): void
    {
        $runner = Participant::factory()
            ->confirmed()
            ->outOfTheRace(ExitReason::Timeout)
            ->create(['event_id' => $this->runningEvent()->getKey()]);

        rescue(fn () => app(WithdrawRunner::class)($runner), report: false);

        $this->assertSame(ExitReason::Timeout, $runner->refresh()->exit_reason);
        $this->assertSame(RunnerStatus::Eliminated, $runner->runnerStatus());
    }

    private function lapsValidated(Event $event, Participant $runner, int $count): void
    {
        foreach (range(1, $count) as $number) {
            Lap::factory()->validated()->for($this->roundOf($event, $number))->for($runner)->create();
        }
    }
}
