<?php

namespace Tests\Feature\Race;

use App\Actions\ReinstateRunner;
use App\Enums\ExitReason;
use App\Enums\LapStatus;
use App\Enums\RunnerStatus;
use App\Exceptions\LapCorrectionRefusedException;
use App\Models\Event;
use App\Models\Lap;
use App\Models\Participant;
use App\Models\Round;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\RunsARace;
use Tests\TestCase;

class ReinstateRunnerTest extends TestCase
{
    use RefreshDatabase;
    use RunsARace;

    #[Test]
    public function it_puts_the_runner_back_in_the_race_on_the_time_the_manager_gave(): void
    {
        $lap = $this->eliminatedLap();
        $this->travelTo($this->at('2026-09-05 18:04'));

        $performance = app(ReinstateRunner::class)($lap, $this->at('2026-09-05 17:58'));

        $this->assertSame(3480, $performance->durationSeconds);
        $this->assertSame(6.21, $performance->speedKmh);
        $this->assertSame(LapStatus::Validated, $lap->refresh()->status);
        $this->assertSame('17:58:00', $lap->validated_at?->format('H:i:s'));
        $this->assertSame(RunnerStatus::Running, $lap->participant->refresh()->runnerStatus());
    }

    #[Test]
    public function it_marks_the_lap_it_corrected(): void
    {
        $lap = $this->eliminatedLap();
        $this->travelTo($this->at('2026-09-05 18:04:30'));

        app(ReinstateRunner::class)($lap, $this->at('2026-09-05 17:58'));

        $this->assertSame('18:04:30', $lap->refresh()->corrected_at?->format('H:i:s'));
    }

    #[Test]
    public function it_accepts_a_time_past_the_deadline_of_the_round(): void
    {
        $lap = $this->eliminatedLap();
        $this->travelTo($this->at('2026-09-05 18:10'));

        $performance = app(ReinstateRunner::class)($lap, $this->at('2026-09-05 18:06'));

        $this->assertSame(3960, $performance->durationSeconds);
        $this->assertSame(LapStatus::Validated, $lap->refresh()->status);
    }

    #[Test]
    public function it_refuses_a_time_before_the_round_started(): void
    {
        $lap = $this->eliminatedLap();

        $this->expectException(LapCorrectionRefusedException::class);
        $this->expectExceptionMessage('précède le départ du tour');

        app(ReinstateRunner::class)($lap, $this->at('2026-09-05 16:59'));
    }

    #[Test]
    public function it_leaves_the_runner_out_when_the_time_is_refused(): void
    {
        $lap = $this->eliminatedLap();

        rescue(fn () => app(ReinstateRunner::class)($lap, $this->at('2026-09-05 16:59')), report: false);

        $this->assertSame(LapStatus::Eliminated, $lap->refresh()->status);
        $this->assertNotNull($lap->participant->refresh()->exited_at);
    }

    #[Test]
    public function it_refuses_a_lap_that_is_already_validated(): void
    {
        $lap = $this->eliminatedLap();
        $lap->update(['status' => LapStatus::Validated, 'validated_at' => $this->at('2026-09-05 17:30')]);

        $this->expectException(LapCorrectionRefusedException::class);
        $this->expectExceptionMessage('déjà validée');

        app(ReinstateRunner::class)($lap, $this->at('2026-09-05 17:58'));
    }

    #[Test]
    public function it_erases_the_reason_a_runner_who_gave_up_had_been_given(): void
    {
        $lap = $this->eliminatedLap(ExitReason::Withdrawal);

        app(ReinstateRunner::class)($lap, $this->at('2026-09-05 17:58'));

        $runner = $lap->participant->refresh();
        $this->assertNull($runner->exit_reason);
        $this->assertNull($runner->exited_at);
        $this->assertTrue($runner->isRunning());
    }

    #[Test]
    public function it_validates_a_lap_the_chrono_had_not_closed_yet(): void
    {
        $event = $this->correctableEvent();
        $round = $this->roundOf($event);
        $lap = $round->laps()->create(['participant_id' => $this->runner($event)->id]);
        $this->travelTo($this->at('2026-09-05 18:00:20'));

        app(ReinstateRunner::class)($lap, $this->at('2026-09-05 17:59:58'));

        $this->assertSame(LapStatus::Validated, $lap->refresh()->status);
        $this->assertSame('17:59:58', $lap->validated_at?->format('H:i:s'));
    }

    #[Test]
    public function it_leaves_the_rounds_that_followed_the_corrected_one_alone(): void
    {
        $event = $this->correctableEvent();
        $runner = $this->runner($event);
        $missed = $this->lapOf($this->roundOf($event, 1), $runner);
        $later = $this->lapOf($this->roundOf($event, 2), $runner);
        $runner->leaveRace(ExitReason::Timeout, $this->at('2026-09-05 18:00'));

        app(ReinstateRunner::class)($missed->refresh(), $this->at('2026-09-05 17:58'));

        $this->assertSame(LapStatus::Validated, $missed->refresh()->status);
        $this->assertSame(LapStatus::Eliminated, $later->refresh()->status);
        $this->assertTrue($runner->refresh()->isRunning());
    }

    private function correctableEvent(): Event
    {
        return Event::factory()->running()->create([
            'first_start_at' => $this->at('2026-09-05 17:00'),
            'lap_duration_minutes' => 60,
            'lap_distance_meters' => 6000,
        ]);
    }

    private function eliminatedLap(ExitReason $reason = ExitReason::Timeout): Lap
    {
        $event = $this->correctableEvent();
        $runner = $this->runner($event);
        $lap = $this->lapOf($this->roundOf($event), $runner);

        $runner->leaveRace($reason, $this->at('2026-09-05 18:00'));

        return $lap->refresh();
    }

    private function lapOf(Round $round, Participant $runner): Lap
    {
        return $round->laps()->create(['participant_id' => $runner->id]);
    }
}
