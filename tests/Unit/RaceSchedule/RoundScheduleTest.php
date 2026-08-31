<?php

namespace Tests\Unit\RaceSchedule;

use App\Models\Event;
use App\Models\ScheduleSegment;
use App\Services\RaceSchedule\RoundSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RoundScheduleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_starts_the_first_round_at_the_first_start(): void
    {
        $this->assertSame('13:00', $this->schedule()->startOf(1)->format('H:i'));
    }

    #[Test]
    public function it_ends_the_first_round_one_lap_later(): void
    {
        $this->assertSame('14:00', $this->schedule()->deadlineOf(1)->format('H:i'));
    }

    #[Test]
    public function it_places_the_fourth_round_three_laps_after_the_first(): void
    {
        $schedule = $this->schedule();

        $this->assertSame('16:00', $schedule->startOf(4)->format('H:i'));
        $this->assertSame('17:00', $schedule->deadlineOf(4)->format('H:i'));
    }

    #[Test]
    public function it_ends_a_round_where_the_next_one_starts(): void
    {
        $schedule = $this->schedule();

        $this->assertSame(
            $schedule->startOf(8)->getTimestamp(),
            $schedule->deadlineOf(7)->getTimestamp(),
        );
    }

    #[Test]
    public function it_places_the_third_round_of_a_forty_five_minute_lap_at_ten_thirty(): void
    {
        $schedule = $this->schedule('2026-09-05 09:00', 45);

        $this->assertSame('10:30', $schedule->startOf(3)->format('H:i'));
    }

    #[Test]
    public function it_resolves_the_current_round_from_the_server_time(): void
    {
        $schedule = $this->schedule();

        $this->assertSame(5, $schedule->numberAt($this->at('2026-09-05 17:30')));
        $this->assertSame('17:00', $schedule->startOf(5)->format('H:i'));
        $this->assertSame('18:00', $schedule->deadlineOf(5)->format('H:i'));
    }

    #[Test]
    public function it_has_no_current_round_before_the_first_start(): void
    {
        $this->assertNull($this->schedule()->numberAt($this->at('2026-09-05 12:59:59')));
    }

    #[Test]
    public function it_opens_the_first_round_on_the_exact_first_start_second(): void
    {
        $this->assertSame(1, $this->schedule()->numberAt($this->at('2026-09-05 13:00:00')));
    }

    #[Test]
    public function it_moves_to_the_next_round_on_the_exact_deadline_second(): void
    {
        $this->assertSame(2, $this->schedule()->numberAt($this->at('2026-09-05 14:00:00')));
    }

    #[Test]
    public function it_keeps_every_round_one_real_hour_long_across_the_autumn_clock_change(): void
    {
        $schedule = $this->schedule('2026-10-24 13:00');

        $ambiguous = $schedule->startOf(14);
        $repeated = $schedule->startOf(15);

        $this->assertSame(3600, $repeated->getTimestamp() - $ambiguous->getTimestamp());
        $this->assertSame('02:00', $ambiguous->format('H:i'));
        $this->assertSame('02:00', $repeated->format('H:i'));
        $this->assertSame('+02:00', $ambiguous->format('P'));
        $this->assertSame('+01:00', $repeated->format('P'));
    }

    #[Test]
    public function it_skips_the_hour_that_does_not_exist_on_the_spring_clock_change(): void
    {
        $schedule = $this->schedule('2027-03-28 01:00', 45);

        $this->assertSame('01:45', $schedule->startOf(2)->format('H:i'));
        $this->assertSame('03:30', $schedule->startOf(3)->format('H:i'));
    }

    #[Test]
    public function it_shortens_every_round_from_the_one_the_change_takes_effect_on(): void
    {
        $schedule = $this->schedule(segments: [3 => 55]);

        $this->assertSame('15:00', $schedule->startOf(3)->format('H:i'));
        $this->assertSame('15:55', $schedule->deadlineOf(3)->format('H:i'));
        $this->assertSame('15:55', $schedule->startOf(4)->format('H:i'));
        $this->assertSame('16:50', $schedule->deadlineOf(4)->format('H:i'));
    }

    #[Test]
    public function it_leaves_the_rounds_before_the_change_where_they_were(): void
    {
        $schedule = $this->schedule(segments: [3 => 55]);

        $this->assertSame('13:00', $schedule->startOf(1)->format('H:i'));
        $this->assertSame('14:00', $schedule->deadlineOf(1)->format('H:i'));
        $this->assertSame('14:00', $schedule->startOf(2)->format('H:i'));
    }

    #[Test]
    public function it_returns_to_the_previous_duration_when_a_second_change_restores_it(): void
    {
        $schedule = $this->schedule(segments: [3 => 55, 4 => 60]);

        $this->assertSame('15:00', $schedule->startOf(3)->format('H:i'));
        $this->assertSame('15:55', $schedule->deadlineOf(3)->format('H:i'));
        $this->assertSame('16:55', $schedule->deadlineOf(4)->format('H:i'));
    }

    #[Test]
    public function it_resolves_the_current_round_against_the_changed_duration(): void
    {
        $schedule = $this->schedule(segments: [3 => 55]);

        $this->assertSame(3, $schedule->numberAt($this->at('2026-09-05 15:30')));
        $this->assertSame(4, $schedule->numberAt($this->at('2026-09-05 15:55')));
        $this->assertSame(2, $schedule->numberAt($this->at('2026-09-05 14:59:59')));
    }

    #[Test]
    public function it_applies_a_change_that_takes_effect_on_the_very_first_round(): void
    {
        $schedule = $this->schedule(segments: [1 => 45]);

        $this->assertSame('13:00', $schedule->startOf(1)->format('H:i'));
        $this->assertSame('13:45', $schedule->startOf(2)->format('H:i'));
        $this->assertSame(2, $schedule->numberAt($this->at('2026-09-05 13:45')));
    }

    #[Test]
    public function it_reads_the_duration_in_force_on_a_round(): void
    {
        $schedule = $this->schedule(segments: [3 => 55, 6 => 40]);

        $this->assertSame(60, $schedule->durationOf(2));
        $this->assertSame(55, $schedule->durationOf(3));
        $this->assertSame(55, $schedule->durationOf(5));
        $this->assertSame(40, $schedule->durationOf(9));
    }

    #[Test]
    public function it_reads_the_changes_recorded_against_the_event(): void
    {
        $event = Event::factory()->running()->create([
            'first_start_at' => $this->at('2026-09-05 13:00'),
            'lap_duration_minutes' => 60,
        ]);
        ScheduleSegment::factory()->for($event)->from(3, 55)->create();

        $this->assertSame('15:55', RoundSchedule::fromEvent($event)?->deadlineOf(3)->format('H:i'));
    }

    #[Test]
    public function it_has_no_schedule_without_a_first_start(): void
    {
        $this->assertNull(RoundSchedule::fromEvent($this->event(['first_start_at' => null])));
    }

    #[Test]
    public function it_has_no_schedule_without_a_lap_duration(): void
    {
        $this->assertNull(RoundSchedule::fromEvent($this->event(['lap_duration_minutes' => null])));
    }

    #[Test]
    public function it_has_no_schedule_when_the_lap_duration_is_under_a_minute(): void
    {
        $this->assertNull(RoundSchedule::fromEvent($this->event(['lap_duration_minutes' => 0])));
    }

    #[Test]
    public function it_builds_a_schedule_from_a_configured_event(): void
    {
        $event = $this->event([
            'first_start_at' => $this->at('2026-09-05 13:00'),
            'lap_duration_minutes' => 60,
        ]);

        $this->assertSame('16:00', RoundSchedule::fromEvent($event)?->startOf(4)->format('H:i'));
    }

    /**
     * @param  array<int, int>  $segments
     */
    private function schedule(string $firstStartAt = '2026-09-05 13:00', int $lapDurationMinutes = 60, array $segments = []): RoundSchedule
    {
        return new RoundSchedule($this->at($firstStartAt), $lapDurationMinutes, $segments);
    }

    private function at(string $instant): CarbonImmutable
    {
        return CarbonImmutable::parse($instant, 'Europe/Paris');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function event(array $attributes): Event
    {
        return Event::factory()->make($attributes);
    }
}
