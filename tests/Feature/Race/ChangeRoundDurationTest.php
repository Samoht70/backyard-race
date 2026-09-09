<?php

namespace Tests\Feature\Race;

use App\Actions\ChangeRoundDuration;
use App\Actions\OpenDueRounds;
use App\Actions\RevertEventStatus;
use App\Enums\EventStatus;
use App\Enums\ScheduleChange;
use App\Exceptions\RoundDurationRefusedException;
use App\Models\Event;
use App\Models\Round;
use App\Models\ScheduleSegment;
use App\Services\RaceSchedule\CurrentRound;
use App\Services\RaceSchedule\ResolveCurrentRound;
use App\Services\RaceSchedule\RoundSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\RunsARace;
use Tests\TestCase;

class ChangeRoundDurationTest extends TestCase
{
    use RefreshDatabase;
    use RunsARace;

    #[Test]
    public function it_shortens_the_next_round_and_every_round_after_it(): void
    {
        $this->travelTo($this->at('2026-09-05 14:30'));
        $event = $this->runningEvent();

        $this->change($event, 3, 55, ScheduleChange::Onwards);

        $this->assertSame('15:55', $this->schedule($event)->deadlineOf(3)->format('H:i'));
        $this->assertSame('16:50', $this->schedule($event)->deadlineOf(4)->format('H:i'));
        $this->assertSame('14:00', $this->schedule($event)->deadlineOf(1)->format('H:i'));
    }

    #[Test]
    public function it_restores_the_previous_duration_at_the_round_after_a_single_round_change(): void
    {
        $this->travelTo($this->at('2026-09-05 14:30'));
        $event = $this->runningEvent();

        $this->change($event, 3, 55, ScheduleChange::SingleRound);

        $this->assertSame('15:55', $this->schedule($event)->deadlineOf(3)->format('H:i'));
        $this->assertSame('16:55', $this->schedule($event)->deadlineOf(4)->format('H:i'));
        $this->assertDatabaseHas('schedule_segments', ['from_round_number' => 4, 'lap_duration_minutes' => 60]);
    }

    #[Test]
    public function it_refuses_a_round_that_has_already_opened(): void
    {
        $this->travelTo($this->at('2026-09-05 15:30'));
        $event = $this->runningEvent();
        app(OpenDueRounds::class)($event);

        $this->expectException(RoundDurationRefusedException::class);

        try {
            $this->change($event, 3, 55, ScheduleChange::Onwards);
        } finally {
            $this->assertDatabaseCount('schedule_segments', 0);
        }
    }

    #[Test]
    public function it_opens_the_round_due_but_not_yet_materialised_and_then_refuses_it(): void
    {
        $this->travelTo($this->at('2026-09-05 13:30'));
        $event = $this->runningEvent();
        app(OpenDueRounds::class)($event);
        $this->travelTo($this->at('2026-09-05 14:00:40'));

        $this->expectException(RoundDurationRefusedException::class);

        try {
            $this->change($event, 2, 55, ScheduleChange::Onwards);
        } finally {
            $this->assertSame([1, 2], Round::query()->orderBy('number')->pluck('number')->all());
            $this->assertDatabaseCount('schedule_segments', 0);
        }
    }

    #[Test]
    public function it_replaces_a_change_already_recorded_on_the_same_round(): void
    {
        $this->travelTo($this->at('2026-09-05 14:30'));
        $event = $this->runningEvent();

        $this->change($event, 3, 55, ScheduleChange::Onwards);
        $this->change($event, 3, 50, ScheduleChange::Onwards);

        $this->assertSame('15:50', $this->schedule($event)->deadlineOf(3)->format('H:i'));
        $this->assertSame(1, ScheduleSegment::query()->where('from_round_number', 3)->count());
    }

    #[Test]
    public function it_writes_nothing_more_when_the_same_change_is_sent_twice(): void
    {
        $this->travelTo($this->at('2026-09-05 14:30'));
        $event = $this->runningEvent();

        $this->change($event, 3, 55, ScheduleChange::SingleRound);
        $this->change($event, 3, 55, ScheduleChange::SingleRound);

        $this->assertDatabaseCount('schedule_segments', 2);
        $this->assertSame('16:55', $this->schedule($event)->deadlineOf(4)->format('H:i'));
    }

    #[Test]
    public function it_resumes_the_duration_a_previous_change_had_set_rather_than_the_configured_one(): void
    {
        $this->travelTo($this->at('2026-09-05 14:30'));
        $event = $this->runningEvent();

        $this->change($event, 3, 55, ScheduleChange::Onwards);
        $this->change($event, 3, 50, ScheduleChange::SingleRound);

        $this->assertSame('15:50', $this->schedule($event)->deadlineOf(3)->format('H:i'));
        $this->assertSame('16:45', $this->schedule($event)->deadlineOf(4)->format('H:i'));
        $this->assertDatabaseHas('schedule_segments', ['from_round_number' => 4, 'lap_duration_minutes' => 55]);
    }

    #[Test]
    public function it_keeps_the_changes_when_the_event_goes_back_to_a_draft(): void
    {
        $event = Event::factory()->registration()->create([
            'first_start_at' => $this->at('2026-09-05 13:00'),
            'lap_duration_minutes' => 60,
        ]);

        $this->change($event, 3, 55, ScheduleChange::Onwards);
        app(RevertEventStatus::class)($event, EventStatus::Draft);

        $this->assertDatabaseHas('schedule_segments', ['from_round_number' => 3, 'lap_duration_minutes' => 55]);
        $this->assertSame('15:55', $this->schedule($event)->deadlineOf(3)->format('H:i'));
    }

    #[Test]
    public function it_reads_the_current_round_from_the_changed_grid(): void
    {
        $this->travelTo($this->at('2026-09-05 14:30'));
        $event = $this->runningEvent();
        $this->change($event, 3, 55, ScheduleChange::Onwards);

        $this->travelTo($this->at('2026-09-05 15:30'));
        $current = $this->currentRound($event);

        $this->assertSame(3, $current?->number);
        $this->assertSame('15:00', $current?->startsAt->format('H:i'));
        $this->assertSame('15:55', $current?->deadlineAt->format('H:i'));
    }

    #[Test]
    public function it_opens_the_round_that_follows_a_change_on_the_changed_deadline(): void
    {
        $this->travelTo($this->at('2026-09-05 14:30'));
        $event = $this->runningEvent();
        $this->change($event, 3, 55, ScheduleChange::Onwards);

        $this->travelTo($this->at('2026-09-05 15:55'));
        app(OpenDueRounds::class)($event);

        $this->assertSame([1, 2, 3, 4], Round::query()->orderBy('number')->pluck('number')->all());
        $this->assertSame('15:55', Round::query()->where('number', 4)->sole()->starts_at->format('H:i'));
        $this->assertSame('15:55', Round::query()->where('number', 3)->sole()->deadline_at->format('H:i'));
    }

    #[Test]
    public function it_refuses_a_change_on_an_event_without_a_grid(): void
    {
        $event = Event::factory()->running()->incomplete()->create();

        $this->expectException(RoundDurationRefusedException::class);

        $this->change($event, 2, 55, ScheduleChange::Onwards);
    }

    private function change(Event $event, int $from, int $minutes, ScheduleChange $scope): void
    {
        app(ChangeRoundDuration::class)($event, $from, $minutes, $scope);
    }

    private function schedule(Event $event): RoundSchedule
    {
        $event->unsetRelation('scheduleSegments');
        $schedule = RoundSchedule::fromEvent($event);

        $this->assertNotNull($schedule);

        return $schedule;
    }

    private function currentRound(Event $event): ?CurrentRound
    {
        $event->unsetRelation('scheduleSegments');

        return app(ResolveCurrentRound::class)($event);
    }
}
