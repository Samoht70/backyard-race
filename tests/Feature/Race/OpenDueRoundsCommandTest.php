<?php

namespace Tests\Feature\Race;

use Illuminate\Console\Scheduling\Event as ScheduledEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\RunsARace;
use Tests\TestCase;

class OpenDueRoundsCommandTest extends TestCase
{
    use RefreshDatabase;
    use RunsARace;

    #[Test]
    public function it_opens_the_due_rounds_when_the_command_runs(): void
    {
        $this->travelTo($this->at('2026-09-05 17:30'));
        $this->runningEvent();

        $this->artisan('race:open-rounds')->assertSuccessful();

        $this->assertDatabaseCount('rounds', 5);
    }

    #[Test]
    public function it_does_nothing_when_no_event_exists(): void
    {
        $this->artisan('race:open-rounds')->assertSuccessful();

        $this->assertDatabaseCount('rounds', 0);
    }

    #[Test]
    public function it_is_scheduled_every_minute(): void
    {
        $expressions = collect(app(Schedule::class)->events())
            ->filter(fn (ScheduledEvent $scheduled): bool => str_contains($scheduled->command ?? '', 'race:open-rounds'))
            ->map(fn (ScheduledEvent $scheduled): string => $scheduled->expression)
            ->all();

        $this->assertSame(['* * * * *'], array_values($expressions));
    }
}
