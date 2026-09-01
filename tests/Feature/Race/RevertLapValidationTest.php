<?php

namespace Tests\Feature\Race;

use App\Actions\RevertLapValidation;
use App\Enums\ExitReason;
use App\Enums\LapStatus;
use App\Enums\RunnerStatus;
use App\Exceptions\LapCorrectionRefusedException;
use App\Models\Event;
use App\Models\Lap;
use App\Models\Round;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\RunsARace;
use Tests\TestCase;

class RevertLapValidationTest extends TestCase
{
    use RefreshDatabase;
    use RunsARace;

    #[Test]
    public function it_puts_the_lap_back_in_the_queue_while_the_round_is_still_open(): void
    {
        $lap = $this->validatedLap('2026-09-05 17:20');
        $this->travelTo($this->at('2026-09-05 17:25'));

        $status = app(RevertLapValidation::class)($lap);

        $this->assertSame(LapStatus::Pending, $status);
        $this->assertSame(LapStatus::Pending, $lap->refresh()->status);
        $this->assertNull($lap->validated_at);
        $this->assertTrue($lap->participant->refresh()->isRunning());
    }

    #[Test]
    public function it_marks_the_lap_it_corrected(): void
    {
        $lap = $this->validatedLap('2026-09-05 17:20');
        $this->travelTo($this->at('2026-09-05 17:25:40'));

        app(RevertLapValidation::class)($lap);

        $this->assertSame('17:25:40', $lap->refresh()->corrected_at?->format('H:i:s'));
    }

    #[Test]
    public function it_takes_the_runner_out_once_the_deadline_has_passed(): void
    {
        $lap = $this->validatedLap('2026-09-05 17:20');
        $this->travelTo($this->at('2026-09-05 18:02'));

        $status = app(RevertLapValidation::class)($lap);

        $this->assertSame(LapStatus::Eliminated, $status);
        $this->assertSame(LapStatus::Eliminated, $lap->refresh()->status);
        $runner = $lap->participant->refresh();
        $this->assertSame(ExitReason::Timeout, $runner->exit_reason);
        $this->assertSame('18:00:00', $runner->exited_at?->format('H:i:s'));
        $this->assertSame(RunnerStatus::Eliminated, $runner->runnerStatus());
    }

    #[Test]
    public function it_keeps_the_reason_of_a_runner_who_was_already_out(): void
    {
        $lap = $this->validatedLap('2026-09-05 17:20');
        $lap->participant->leaveRace(ExitReason::Withdrawal, $this->at('2026-09-05 17:40'));
        $this->travelTo($this->at('2026-09-05 18:02'));

        app(RevertLapValidation::class)($lap);

        $runner = $lap->participant->refresh();
        $this->assertSame(ExitReason::Withdrawal, $runner->exit_reason);
        $this->assertSame('17:40:00', $runner->exited_at?->format('H:i:s'));
    }

    #[Test]
    public function it_refuses_a_lap_that_carries_no_validation(): void
    {
        $lap = $this->validatedLap('2026-09-05 17:20');
        $lap->update(['status' => LapStatus::Pending, 'validated_at' => null]);

        $this->expectException(LapCorrectionRefusedException::class);
        $this->expectExceptionMessage('aucune validation à annuler');

        app(RevertLapValidation::class)($lap);
    }

    #[Test]
    public function it_reads_the_deadline_frozen_on_the_round_rather_than_the_current_grid(): void
    {
        $event = Event::factory()->running()->create([
            'first_start_at' => $this->at('2026-09-05 17:00'),
            'lap_duration_minutes' => 30,
            'lap_distance_meters' => 6000,
        ]);
        $round = Round::factory()
            ->for($event)
            ->numbered(1)
            ->startingAt($this->at('2026-09-05 17:00'), 60)
            ->create();
        $lap = Lap::factory()
            ->validated($this->at('2026-09-05 17:20'))
            ->for($round)
            ->for($this->runner($event))
            ->create();

        $this->travelTo($this->at('2026-09-05 17:45'));

        $this->assertSame(LapStatus::Pending, app(RevertLapValidation::class)($lap));
        $this->assertTrue($lap->participant->refresh()->isRunning());
    }

    private function validatedLap(string $validatedAt): Lap
    {
        $event = Event::factory()->running()->create([
            'first_start_at' => $this->at('2026-09-05 17:00'),
            'lap_duration_minutes' => 60,
            'lap_distance_meters' => 6000,
        ]);

        return Lap::factory()
            ->validated($this->at($validatedAt))
            ->for($this->roundOf($event))
            ->for($this->runner($event))
            ->create();
    }
}
