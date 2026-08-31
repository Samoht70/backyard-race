<?php

namespace Tests\Feature\Race;

use App\Actions\OpenRoundLaps;
use App\Enums\LapStatus;
use App\Models\Lap;
use App\Models\Participant;
use App\Models\Round;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\RunsARace;
use Tests\TestCase;

class OpenRoundLapsTest extends TestCase
{
    use RefreshDatabase;
    use RunsARace;

    #[Test]
    public function it_opens_one_pending_lap_for_every_runner_still_in_the_race(): void
    {
        $event = $this->runningEvent();
        $this->runners($event, 3);
        $round = $this->roundOf($event, 6);

        $opened = $this->open($round);

        $this->assertSame(3, $opened);
        $this->assertSame(3, $round->laps()->where('status', LapStatus::Pending)->count());
    }

    #[Test]
    public function it_opens_no_lap_for_a_runner_already_eliminated(): void
    {
        $event = $this->runningEvent();
        $eliminated = $this->runner($event);
        Lap::factory()->eliminated()->for($this->roundOf($event, 5))->for($eliminated)->create();

        $this->open($this->roundOf($event, 6));

        $this->assertSame(1, $eliminated->laps()->count());
    }

    #[Test]
    public function it_opens_no_lap_for_a_registration_that_is_not_confirmed(): void
    {
        $event = $this->runningEvent();
        $pending = Participant::factory()->create(['event_id' => $event->getKey()]);
        $cancelled = Participant::factory()->cancelled()->create(['event_id' => $event->getKey()]);

        $opened = $this->open($this->roundOf($event));

        $this->assertSame(0, $opened);
        $this->assertSame(0, $pending->laps()->count());
        $this->assertSame(0, $cancelled->laps()->count());
    }

    #[Test]
    public function it_opens_nothing_the_second_time_it_runs_on_the_same_round(): void
    {
        $event = $this->runningEvent();
        $this->runners($event, 4);
        $round = $this->roundOf($event);

        $this->open($round);
        $replayed = $this->open($round);

        $this->assertSame(0, $replayed);
        $this->assertSame(4, $round->laps()->count());
    }

    #[Test]
    public function it_opens_nothing_when_every_runner_is_out(): void
    {
        $event = $this->runningEvent();
        $previous = $this->roundOf($event, 5);
        $this->runners($event, 2)->each(
            fn (Participant $runner) => Lap::factory()->eliminated()->for($previous)->for($runner)->create(),
        );

        $opened = $this->open($this->roundOf($event, 6));

        $this->assertSame(0, $opened);
    }

    #[Test]
    public function it_leaves_a_lap_reading_its_schedule_from_its_round(): void
    {
        $event = $this->runningEvent('2026-09-05 13:00');
        $this->runner($event);
        $round = $this->roundOf($event, 6);

        $this->open($round);
        $lap = $round->laps()->sole();

        $this->assertSame('18:00', $lap->round->starts_at->format('H:i'));
        $this->assertSame('19:00', $lap->round->deadline_at->format('H:i'));
        $this->assertNull($lap->validated_at);
    }

    private function open(Round $round): int
    {
        return app(OpenRoundLaps::class)($round);
    }
}
