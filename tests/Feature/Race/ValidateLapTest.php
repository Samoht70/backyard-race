<?php

namespace Tests\Feature\Race;

use App\Actions\ValidateLap;
use App\Enums\LapStatus;
use App\Exceptions\LapValidationRefusedException;
use App\Models\Event;
use App\Models\Lap;
use App\Models\Participant;
use App\Models\Round;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\RunsARace;
use Tests\TestCase;

class ValidateLapTest extends TestCase
{
    use RefreshDatabase;
    use RunsARace;

    #[Test]
    public function it_records_the_server_time_the_duration_and_the_speed(): void
    {
        $lap = $this->pendingLap('2026-09-05 17:00', 6000);
        $this->travelTo($this->at('2026-09-05 17:47:32'));

        $performance = app(ValidateLap::class)($lap);

        $this->assertSame(2852, $performance->durationSeconds);
        $this->assertSame(7.57, $performance->speedKmh);
        $this->assertSame('17:47:32', $performance->validatedAt->format('H:i:s'));
        $this->assertDatabaseHas('laps', [
            'id' => $lap->id,
            'status' => LapStatus::Validated->value,
        ]);
        $this->assertSame('17:47:32', $lap->refresh()->validated_at?->format('H:i:s'));
    }

    #[Test]
    public function it_keeps_the_first_time_when_the_manager_presses_twice(): void
    {
        $lap = $this->pendingLap('2026-09-05 17:00', 6000);
        $this->travelTo($this->at('2026-09-05 17:47:32'));
        app(ValidateLap::class)($lap);

        $this->travelTo($this->at('2026-09-05 17:52:10'));
        $performance = app(ValidateLap::class)($lap->refresh());

        $this->assertSame('17:47:32', $performance->validatedAt->format('H:i:s'));
        $this->assertSame(2852, $performance->durationSeconds);
        $this->assertSame('17:47:32', $lap->refresh()->validated_at?->format('H:i:s'));
    }

    #[Test]
    public function it_accepts_a_validation_on_the_exact_deadline(): void
    {
        $lap = $this->pendingLap('2026-09-05 17:00', 6000);
        $this->travelTo($this->at('2026-09-05 18:00:00'));

        $performance = app(ValidateLap::class)($lap);

        $this->assertSame(3600, $performance->durationSeconds);
        $this->assertSame(LapStatus::Validated, $lap->refresh()->status);
    }

    #[Test]
    public function it_refuses_a_validation_one_second_past_the_deadline(): void
    {
        $lap = $this->pendingLap('2026-09-05 17:00', 6000);
        $this->travelTo($this->at('2026-09-05 18:00:01'));

        $this->expectException(LapValidationRefusedException::class);
        $this->expectExceptionMessage('correction exceptionnelle');

        app(ValidateLap::class)($lap);
    }

    #[Test]
    public function it_leaves_the_lap_pending_when_the_deadline_refuses_it(): void
    {
        $lap = $this->pendingLap('2026-09-05 17:00', 6000);
        $this->travelTo($this->at('2026-09-05 18:00:01'));

        rescue(fn () => app(ValidateLap::class)($lap), report: false);

        $this->assertSame(LapStatus::Pending, $lap->refresh()->status);
        $this->assertNull($lap->validated_at);
    }

    #[Test]
    public function it_refuses_to_validate_a_lap_the_runner_was_eliminated_on(): void
    {
        $lap = $this->pendingLap('2026-09-05 17:00', 6000);
        $lap->update(['status' => LapStatus::Eliminated]);
        $this->travelTo($this->at('2026-09-05 17:30'));

        $this->expectException(LapValidationRefusedException::class);

        app(ValidateLap::class)($lap);
    }

    #[Test]
    public function it_records_the_duration_without_a_speed_when_the_event_has_no_distance(): void
    {
        $lap = $this->pendingLap('2026-09-05 17:00', null);
        $this->travelTo($this->at('2026-09-05 17:47:32'));

        $performance = app(ValidateLap::class)($lap);

        $this->assertSame(2852, $performance->durationSeconds);
        $this->assertNull($performance->distanceMeters);
        $this->assertNull($performance->speedKmh);
    }

    #[Test]
    public function it_gives_each_of_ten_runners_its_own_time(): void
    {
        $event = $this->runningEvent('2026-09-05 17:00');
        $round = $this->roundOf($event);

        $laps = $this->runners($event, 10)->map(
            fn (Participant $runner): Lap => $round->laps()->create(['participant_id' => $runner->id]),
        );

        foreach ($laps as $position => $lap) {
            $this->travelTo($this->at('2026-09-05 17:45:00')->addSeconds($position));
            app(ValidateLap::class)($lap);
        }

        $this->assertSame(
            ['17:45:00', '17:45:01', '17:45:02'],
            $laps->take(3)->map(fn (Lap $lap): ?string => $lap->refresh()->validated_at?->format('H:i:s'))->all(),
        );
        $this->assertSame(10, $round->laps()->where('status', LapStatus::Validated)->count());
    }

    #[Test]
    public function it_reads_the_deadline_frozen_on_the_round_rather_than_the_current_grid(): void
    {
        $event = $this->runningEvent('2026-09-05 17:00', 30);
        $round = Round::factory()
            ->for($event)
            ->numbered(1)
            ->startingAt($this->at('2026-09-05 17:00'), 60)
            ->create();
        $lap = $round->laps()->create(['participant_id' => $this->runner($event)->id]);

        $this->travelTo($this->at('2026-09-05 17:45'));

        $this->assertSame(2700, app(ValidateLap::class)($lap)->durationSeconds);
    }

    private function pendingLap(string $startsAt, ?int $lapDistanceMeters): Lap
    {
        $event = Event::factory()->running()->create([
            'first_start_at' => $this->at($startsAt),
            'lap_duration_minutes' => 60,
            'lap_distance_meters' => $lapDistanceMeters,
        ]);

        return $this->roundOf($event)->laps()->create([
            'participant_id' => $this->runner($event)->id,
        ]);
    }
}
