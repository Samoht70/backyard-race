<?php

namespace Tests\Feature\Race;

use App\Enums\LapStatus;
use App\Models\Event;
use App\Models\Participant;
use App\Models\Round;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\RunsARace;
use Tests\TestCase;

class LapTest extends TestCase
{
    use RefreshDatabase;
    use RunsARace;

    #[Test]
    public function it_opens_a_lap_pending_and_without_a_validation_time(): void
    {
        $event = $this->runningEvent();

        $lap = $this->round($event)->laps()->create([
            'participant_id' => $this->runner($event)->getKey(),
        ]);

        $lap->refresh();

        $this->assertSame(LapStatus::Pending, $lap->status);
        $this->assertNull($lap->validated_at);
    }

    #[Test]
    public function it_refuses_two_laps_for_one_runner_on_one_round(): void
    {
        $event = $this->runningEvent();
        $round = $this->round($event);
        $runner = $this->runner($event);

        $round->laps()->create(['participant_id' => $runner->getKey()]);

        $this->expectException(UniqueConstraintViolationException::class);

        $round->laps()->create(['participant_id' => $runner->getKey()]);
    }

    #[Test]
    public function it_accepts_one_lap_per_round_for_the_same_runner(): void
    {
        $event = $this->runningEvent();
        $runner = $this->runner($event);

        $this->round($event)->laps()->create(['participant_id' => $runner->getKey()]);
        $this->round($event, 2)->laps()->create(['participant_id' => $runner->getKey()]);

        $this->assertSame(2, $runner->laps()->count());
    }

    #[Test]
    public function it_stores_an_ambiguous_validation_time_without_losing_an_hour(): void
    {
        $event = $this->runningEvent();
        $round = $this->round($event);
        $firstTwo = $this->at('2026-10-25 01:30')->addHour();

        $early = $round->laps()->create([
            'participant_id' => $this->runner($event)->getKey(),
            'status' => LapStatus::Validated,
            'validated_at' => $firstTwo,
        ]);
        $late = $round->laps()->create([
            'participant_id' => $this->runner($event)->getKey(),
            'status' => LapStatus::Validated,
            'validated_at' => $firstTwo->addHour(),
        ]);

        $early->refresh();
        $late->refresh();

        $this->assertSame('02:30', $early->validated_at->format('H:i'));
        $this->assertSame('02:30', $late->validated_at->format('H:i'));
        $this->assertSame(3600, $late->validated_at->getTimestamp() - $early->validated_at->getTimestamp());
    }

    private function round(Event $event, int $number = 1): Round
    {
        return $event->rounds()->create([
            'number' => $number,
            'starts_at' => $this->at('2026-09-05 13:00'),
            'deadline_at' => $this->at('2026-09-05 14:00'),
        ]);
    }

    private function runner(Event $event): Participant
    {
        return Participant::factory()->confirmed()->create(['event_id' => $event->getKey()]);
    }
}
