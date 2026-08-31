<?php

namespace Tests\Feature\Race;

use App\Enums\ExitReason;
use App\Models\Lap;
use App\Models\Round;
use Illuminate\Console\Scheduling\Event as ScheduledEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\RunsARace;
use Tests\TestCase;

class AdvanceRaceCommandTest extends TestCase
{
    use RefreshDatabase;
    use RunsARace;

    #[Test]
    public function it_opens_the_due_rounds_when_the_command_runs(): void
    {
        $this->travelTo($this->at('2026-09-05 17:30'));
        $this->runningEvent();

        $this->artisan('race:advance')->assertSuccessful();

        $this->assertDatabaseCount('rounds', 5);
    }

    #[Test]
    public function it_does_nothing_when_no_event_exists(): void
    {
        $this->artisan('race:advance')->assertSuccessful();

        $this->assertDatabaseCount('rounds', 0);
    }

    #[Test]
    public function it_opens_the_next_round_for_the_runners_the_deadline_left_in_the_race(): void
    {
        $event = $this->runningEvent();
        $round = $this->roundOf($event);
        $arrived = $this->runner($event);
        $overdue = $this->runner($event);
        Lap::factory()->validated($this->at('2026-09-05 13:50'))->for($round)->for($arrived)->create();
        Lap::factory()->for($round)->for($overdue)->create();
        $this->travelTo($this->at('2026-09-05 14:00'));

        $this->artisan('race:advance')->assertSuccessful();

        $this->assertSame(ExitReason::Timeout, $overdue->refresh()->exit_reason);
        $this->assertSame([$arrived->getKey()], Round::query()
            ->where('number', 2)
            ->sole()
            ->laps()
            ->pluck('participant_id')
            ->all());
    }

    #[Test]
    public function it_is_scheduled_every_minute(): void
    {
        $expressions = collect(app(Schedule::class)->events())
            ->filter(fn (ScheduledEvent $scheduled): bool => str_contains($scheduled->command ?? '', 'race:advance'))
            ->map(fn (ScheduledEvent $scheduled): string => $scheduled->expression)
            ->all();

        $this->assertSame(['* * * * *'], array_values($expressions));
    }
}
