<?php

namespace Tests\Feature\Race;

use App\Enums\EventStatus;
use App\Enums\ExitReason;
use App\Enums\RunnerStatus;
use App\Models\Event;
use App\Models\Lap;
use App\Models\Participant;
use App\Models\Round;
use App\Models\Standing;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\RunsARace;
use Tests\TestCase;

class RaceClosureTest extends TestCase
{
    use RefreshDatabase;
    use RunsARace;

    #[Test]
    public function it_ranks_the_runners_on_their_validated_laps(): void
    {
        $event = $this->runningEvent();
        $this->runnerNamed($event, 'Thomas', 18);
        $this->runnerNamed($event, 'Paul', 17);
        $this->runnerNamed($event, 'Julie', 16);

        $this->close($event);

        $this->assertSame(
            [[1, 'Thomas', 18], [2, 'Paul', 17], [3, 'Julie', 16]],
            $this->standings($event),
        );
    }

    #[Test]
    public function it_leaves_two_runners_with_the_same_laps_ex_aequo_and_skips_the_rank_below(): void
    {
        $event = $this->runningEvent();
        $this->runnerNamed($event, 'Ana', 12);
        $this->runnerNamed($event, 'Bruno', 12);
        $this->runnerNamed($event, 'Chloé', 9);

        $this->close($event);

        $this->assertSame(
            [[1, 'Ana', 12], [1, 'Bruno', 12], [3, 'Chloé', 9]],
            $this->standings($event),
        );
    }

    #[Test]
    public function it_classes_the_slower_runner_first_when_he_has_one_lap_more(): void
    {
        $event = $this->runningEvent();
        $this->runnerNamed($event, 'Rapide', 10, minutesPerLap: 30);
        $this->runnerNamed($event, 'Endurant', 11, minutesPerLap: 55);

        $this->close($event);

        $this->assertSame(
            [[1, 'Endurant', 11], [2, 'Rapide', 10]],
            $this->standings($event),
        );
    }

    #[Test]
    public function it_freezes_an_empty_standing_when_nobody_validated_a_lap(): void
    {
        $event = $this->runningEvent();
        $this->runner($event);

        $this->close($event);

        $this->assertSame([], $this->standings($event));
    }

    #[Test]
    public function it_keeps_the_bib_and_the_name_the_runner_raced_under(): void
    {
        $event = $this->runningEvent();
        $runner = $this->runnerNamed($event, 'Camille', 4);

        $this->close($event);

        $runner->user->forceFill(['last_name' => 'Autre'])->save();

        $standing = Standing::query()->sole();

        $this->assertSame('Camille', $standing->first_name);
        $this->assertSame($runner->bib_number, $standing->bib_number);
    }

    #[Test]
    public function it_reads_a_runner_still_in_the_race_as_finished_and_the_others_by_their_exit(): void
    {
        $event = $this->runningEvent();
        $this->runnerNamed($event, 'Arrivé', 8);
        $this->runnerNamed($event, 'Sorti', 5)
            ->leaveRace(ExitReason::Timeout, $this->at('2026-09-05 18:00'));
        $this->runnerNamed($event, 'Parti', 3)
            ->leaveRace(ExitReason::Withdrawal, $this->at('2026-09-05 16:00'));

        $this->close($event);

        $statuses = Standing::query()
            ->orderBy('rank')
            ->get()
            ->mapWithKeys(fn (Standing $standing): array => [
                $standing->first_name => $standing->runnerStatus(),
            ])
            ->all();

        $this->assertSame([
            'Arrivé' => RunnerStatus::Finished,
            'Sorti' => RunnerStatus::Eliminated,
            'Parti' => RunnerStatus::Withdrawn,
        ], $statuses);
    }

    #[Test]
    public function it_records_the_instant_the_race_was_closed(): void
    {
        CarbonImmutable::setTestNow($this->at('2026-09-06 07:30'));

        $event = $this->runningEvent();

        $this->close($event);

        $this->assertTrue($this->at('2026-09-06 07:30')->equalTo(Event::query()->sole()->finished_at));
    }

    #[Test]
    public function it_refuses_to_validate_a_lap_once_the_race_is_closed(): void
    {
        $event = $this->runningEvent();
        $runner = $this->runnerNamed($event, 'Camille', 2);
        $lap = Lap::factory()->create([
            'participant_id' => $runner->getKey(),
            'round_id' => $this->roundOf($event, 9)->getKey(),
        ]);

        $this->close($event);

        $this->actingAs($this->manager())
            ->post(route('manage.laps.validate', $lap))
            ->assertForbidden();
    }

    private function close(Event $event): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->actingAs($this->manager())
            ->post(route('manage.event.advance'), ['to' => EventStatus::Finished->value])
            ->assertRedirect(route('manage.event.edit'));

        $this->assertSame(EventStatus::Finished, $event->refresh()->status);
    }

    /**
     * @return list<array{int, string, int}>
     */
    private function standings(Event $event): array
    {
        return $event->standings()
            ->get()
            ->map(fn (Standing $standing): array => [
                $standing->rank,
                $standing->first_name,
                $standing->validated_laps,
            ])
            ->all();
    }

    private function runnerNamed(Event $event, string $firstName, int $laps, int $minutesPerLap = 45): Participant
    {
        $runner = Participant::factory()
            ->confirmed()
            ->for(User::factory()->state(['first_name' => $firstName]))
            ->create(['event_id' => $event->getKey()]);

        foreach (range(1, $laps) as $number) {
            $round = $this->round($event, $number);

            Lap::factory()
                ->validated($round->starts_at->addMinutes($minutesPerLap))
                ->create([
                    'participant_id' => $runner->getKey(),
                    'round_id' => $round->getKey(),
                ]);
        }

        return $runner;
    }

    private function round(Event $event, int $number): Round
    {
        return $event->rounds()->firstWhere('number', $number) ?? $this->roundOf($event, $number);
    }

    private function manager(): User
    {
        return User::factory()->manager()->create();
    }
}
