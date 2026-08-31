<?php

namespace Tests\Feature\Race;

use App\Enums\RunnerStatus;
use App\Models\Lap;
use App\Models\Participant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\RunsARace;
use Tests\TestCase;

class RunnerStatusTest extends TestCase
{
    use RefreshDatabase;
    use RunsARace;

    #[Test]
    public function it_reports_a_confirmed_runner_without_an_eliminated_lap_as_running(): void
    {
        $event = $this->runningEvent();
        $runner = $this->runner($event);
        Lap::factory()->validated()->for($this->roundOf($event))->for($runner)->create();

        $this->assertTrue($runner->isRunning());
        $this->assertSame(RunnerStatus::Running, $runner->runnerStatus());
    }

    #[Test]
    public function it_reports_a_runner_with_an_eliminated_lap_as_eliminated(): void
    {
        $event = $this->runningEvent();
        $runner = $this->runner($event);
        Lap::factory()->eliminated()->for($this->roundOf($event))->for($runner)->create();

        $this->assertFalse($runner->isRunning());
        $this->assertSame(RunnerStatus::Eliminated, $runner->runnerStatus());
    }

    #[Test]
    public function it_leaves_out_a_registration_that_is_not_confirmed(): void
    {
        $event = $this->runningEvent();
        Participant::factory()->create(['event_id' => $event->getKey()]);
        Participant::factory()->cancelled()->create(['event_id' => $event->getKey()]);
        $running = $this->runner($event);

        $this->assertSame([$running->getKey()], Participant::query()->running()->pluck('id')->all());
    }

    #[Test]
    public function it_reads_the_status_of_a_whole_roster_without_a_query_per_runner(): void
    {
        $event = $this->runningEvent();
        $round = $this->roundOf($event);
        $running = $this->runner($event);
        $eliminated = $this->runner($event);
        Lap::factory()->validated()->for($round)->for($running)->create();
        Lap::factory()->eliminated()->for($round)->for($eliminated)->create();

        $batched = Participant::query()->withRaceStatus()->orderBy('id')->get();
        $plain = Participant::query()->orderBy('id')->get();

        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $statuses = $batched->map(fn (Participant $runner): RunnerStatus => $runner->runnerStatus())->all();
        $this->assertSame([RunnerStatus::Running, RunnerStatus::Eliminated], $statuses);
        $this->assertSame(0, $queries);

        $plain->each(fn (Participant $runner): RunnerStatus => $runner->runnerStatus());
        $this->assertSame($plain->count(), $queries);
    }

    #[Test]
    public function it_counts_the_validated_laps_of_a_whole_roster_in_one_query(): void
    {
        $event = $this->runningEvent();
        $runner = $this->runner($event);
        Lap::factory()->validated()->for($this->roundOf($event))->for($runner)->create();
        Lap::factory()->for($this->roundOf($event, 2))->for($runner)->create();

        $loaded = Participant::query()->withValidatedLapsCount()->sole();

        $this->assertSame(1, $loaded->validatedLapsCount());
        $this->assertSame(1, $runner->validatedLapsCount());
    }

    #[Test]
    public function it_keeps_an_eliminated_runner_out_of_the_active_roster(): void
    {
        $event = $this->runningEvent();
        $round = $this->roundOf($event);
        $running = $this->runner($event);
        $eliminated = $this->runner($event);
        Lap::factory()->eliminated()->for($round)->for($eliminated)->create();
        Lap::factory()->validated()->for($round)->for($running)->create();

        $this->assertSame([$running->getKey()], Participant::query()->running()->pluck('id')->all());
    }
}
