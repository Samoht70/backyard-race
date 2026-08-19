<?php

namespace Tests\Feature\Race;

use App\Actions\OpenDueRounds;
use App\Models\Event;
use App\Models\Round;
use App\Services\RaceSchedule\RoundSchedule;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\RunsARace;
use Tests\TestCase;

class OpenDueRoundsTest extends TestCase
{
    use RefreshDatabase;
    use RunsARace;

    #[Test]
    public function it_opens_every_round_due_at_the_server_time(): void
    {
        $this->travelTo($this->at('2026-09-05 17:30'));
        $event = $this->runningEvent();

        $opened = $this->open($event);

        $this->assertCount(5, $opened);
        $this->assertSame([1, 2, 3, 4, 5], $this->openedNumbers());
    }

    #[Test]
    public function it_stores_the_deadline_of_a_round_as_the_start_of_the_next(): void
    {
        $this->travelTo($this->at('2026-09-05 17:30'));
        $this->open($this->runningEvent());

        $this->assertSame(
            $this->round(4)->starts_at->getTimestamp(),
            $this->round(3)->deadline_at->getTimestamp(),
        );
    }

    #[Test]
    public function it_opens_nothing_before_the_first_start(): void
    {
        $this->travelTo($this->at('2026-09-05 12:59'));

        $this->assertSame([], $this->open($this->runningEvent()));
        $this->assertDatabaseCount('rounds', 0);
    }

    #[Test]
    public function it_opens_nothing_while_the_event_is_not_running(): void
    {
        $this->travelTo($this->at('2026-09-05 17:30'));
        $event = Event::factory()->registration()->create([
            'first_start_at' => $this->at('2026-09-05 13:00'),
            'lap_duration_minutes' => 60,
        ]);

        $this->assertSame([], $this->open($event));
        $this->assertDatabaseCount('rounds', 0);
    }

    #[Test]
    public function it_opens_nothing_once_the_event_is_finished(): void
    {
        $this->travelTo($this->at('2026-09-05 17:30'));
        $event = Event::factory()->finished()->create([
            'first_start_at' => $this->at('2026-09-05 13:00'),
            'lap_duration_minutes' => 60,
        ]);

        $this->assertSame([], $this->open($event));
        $this->assertDatabaseCount('rounds', 0);
    }

    #[Test]
    public function it_opens_nothing_when_the_configuration_is_incomplete(): void
    {
        $this->travelTo($this->at('2026-09-05 17:30'));

        $this->assertSame([], $this->open(Event::factory()->running()->incomplete()->create()));
        $this->assertDatabaseCount('rounds', 0);
    }

    #[Test]
    public function it_opens_no_duplicate_round_when_replayed(): void
    {
        $this->travelTo($this->at('2026-09-05 17:30'));
        $event = $this->runningEvent();

        $this->open($event);
        $openedAt = $this->round(1)->created_at;

        $this->travelTo($this->at('2026-09-05 17:45'));

        $this->assertSame([], $this->open($event));
        $this->assertDatabaseCount('rounds', 5);
        $this->assertEquals($openedAt, $this->round(1)->created_at);
    }

    #[Test]
    public function it_opens_only_the_missing_rounds_after_a_scheduler_outage(): void
    {
        $this->travelTo($this->at('2026-09-05 14:30'));
        $event = $this->runningEvent();
        $this->open($event);
        $openedAt = $this->round(2)->created_at;

        $this->travelTo($this->at('2026-09-05 17:30'));
        $opened = $this->open($event);

        $this->assertSame([3, 4, 5], array_map(fn (Round $round): int => $round->number, $opened));
        $this->assertSame([1, 2, 3, 4, 5], $this->openedNumbers());
        $this->assertEquals($openedAt, $this->round(2)->created_at);
    }

    #[Test]
    public function it_keeps_the_scheduled_deadline_when_it_runs_late(): void
    {
        $this->travelTo($this->at('2026-09-05 17:04'));
        $this->open($this->runningEvent());

        $this->assertSame('17:00', $this->round(5)->starts_at->format('H:i'));
        $this->assertSame('18:00', $this->round(5)->deadline_at->format('H:i'));
    }

    #[Test]
    public function it_refuses_two_rounds_with_the_same_number_on_one_event(): void
    {
        $this->travelTo($this->at('2026-09-05 14:30'));
        $event = $this->runningEvent();
        $this->open($event);

        $this->expectException(UniqueConstraintViolationException::class);

        Round::query()->create([
            'event_id' => $event->getKey(),
            'number' => 1,
            'starts_at' => $this->at('2026-09-05 13:00'),
            'deadline_at' => $this->at('2026-09-05 14:00'),
        ]);
    }

    #[Test]
    public function it_stores_an_ambiguous_round_start_without_losing_an_hour(): void
    {
        $this->travelTo($this->at('2026-10-25 03:30'));
        $event = Event::factory()->running()->create([
            'first_start_at' => $this->at('2026-10-24 13:00'),
            'lap_duration_minutes' => 60,
        ]);
        $schedule = RoundSchedule::fromEvent($event);

        $this->open($event);

        $this->assertSame($schedule?->startOf(14)->getTimestamp(), $this->round(14)->starts_at->getTimestamp());
        $this->assertSame($schedule?->startOf(15)->getTimestamp(), $this->round(15)->starts_at->getTimestamp());
        $this->assertSame('02:00', $this->round(14)->starts_at->format('H:i'));
        $this->assertSame('02:00', $this->round(15)->starts_at->format('H:i'));
        $this->assertSame(3600, $this->round(15)->starts_at->getTimestamp() - $this->round(14)->starts_at->getTimestamp());
    }

    /**
     * @return list<Round>
     */
    private function open(Event $event): array
    {
        return app(OpenDueRounds::class)($event);
    }

    private function round(int $number): Round
    {
        return Round::query()->where('number', $number)->sole();
    }

    /**
     * @return list<int>
     */
    private function openedNumbers(): array
    {
        return Round::query()->orderBy('number')->pluck('number')->all();
    }
}
