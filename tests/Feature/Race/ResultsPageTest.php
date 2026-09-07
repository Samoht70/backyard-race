<?php

namespace Tests\Feature\Race;

use App\Enums\ExitReason;
use App\Enums\LapStatus;
use App\Models\Event;
use App\Models\Lap;
use App\Models\Participant;
use App\Models\Round;
use App\Models\Standing;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ResultsPageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_turns_the_home_page_into_the_results_once_the_race_is_closed(): void
    {
        $event = $this->closedEvent();
        $this->winner($event, firstName: 'Thomas', laps: 18);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->component('Results')
                ->where('winners.0.first_name', 'Thomas')
                ->where('winners.0.validated_laps', 18)
                ->where('winners.0.covered_meters', 108000)
            );
    }

    #[Test]
    public function it_keeps_announcing_the_event_while_the_race_is_still_running(): void
    {
        Event::factory()->running()->create();

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->component('Event'));
    }

    #[Test]
    public function it_keeps_announcing_the_event_while_registrations_are_open(): void
    {
        Event::factory()->registration()->create();

        $this->get(route('home'))
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->component('Event'));
    }

    #[Test]
    public function it_hides_a_closed_race_from_a_guest_who_cannot_see_the_event(): void
    {
        Event::factory()->create();

        $this->get(route('home'))
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->component('Event')
                ->where('event', null)
            );
    }

    #[Test]
    public function it_counts_the_participants_the_laps_the_kilometers_and_the_duration(): void
    {
        $event = $this->closedEvent();
        $round = $this->round($event, 1);
        $this->validatedLaps($round, 3);
        $this->leavesTheRace($round, ExitReason::Withdrawal);

        $this->get(route('home'))
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->where('totals.participants', 4)
                ->where('totals.validated_laps', 3)
                ->where('totals.covered_meters', 18000)
                ->where('totals.duration_seconds', 54240)
            );
    }

    #[Test]
    public function it_leaves_the_kilometers_unknown_when_no_lap_distance_is_configured(): void
    {
        $event = $this->closedEvent(['lap_distance_meters' => null]);
        $this->validatedLaps($this->round($event, 1), 2);

        $this->get(route('home'))
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->where('totals.validated_laps', 2)
                ->where('totals.covered_meters', null)
            );
    }

    #[Test]
    public function it_counts_the_completed_laps_of_every_round(): void
    {
        $event = $this->closedEvent();
        $this->validatedLaps($this->round($event, 3), 32);
        $this->validatedLaps($this->round($event, 5), 20);

        $this->get(route('home'))
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->where('rounds.0.number', 3)
                ->where('rounds.0.completed_laps', 32)
                ->where('rounds.1.number', 5)
                ->where('rounds.1.completed_laps', 20)
            );
    }

    #[Test]
    public function it_tells_the_withdrawals_of_a_round_from_its_timeouts(): void
    {
        $event = $this->closedEvent();
        $round = $this->round($event, 4);
        $this->validatedLaps($round, 2);

        $this->leaveTheRace($round, 3, ExitReason::Withdrawal);
        $this->leaveTheRace($round, 5, ExitReason::Timeout);

        $this->get(route('home'))
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->where('rounds.0.runners', 10)
                ->where('rounds.0.completed_laps', 2)
                ->where('rounds.0.withdrawals', 3)
                ->where('rounds.0.timeouts', 5)
            );
    }

    #[Test]
    public function it_announces_every_runner_tied_at_the_top(): void
    {
        $event = $this->closedEvent();
        $this->winner($event, firstName: 'Thomas', laps: 12, bibNumber: 7);
        $this->winner($event, firstName: 'Paul', laps: 12, bibNumber: 41);
        $this->winner($event, firstName: 'Camille', laps: 9, bibNumber: 3, rank: 3);

        $this->get(route('home'))
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->count('winners', 2)
                ->where('winners.0.first_name', 'Thomas')
                ->where('winners.1.first_name', 'Paul')
            );
    }

    #[Test]
    public function it_announces_no_winner_and_zeroes_when_not_a_single_lap_was_validated(): void
    {
        $this->closedEvent();

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->component('Results')
                ->where('winners', [])
                ->where('rounds', [])
                ->where('totals.participants', 0)
                ->where('totals.validated_laps', 0)
                ->where('totals.covered_meters', 0)
            );
    }

    #[Test]
    public function it_reads_every_round_in_a_fixed_number_of_queries(): void
    {
        $event = $this->closedEvent();
        $this->validatedLaps($this->round($event, 1), 2);
        $onOneRound = $this->queriesOfTheResults();

        foreach (range(2, 6) as $number) {
            $this->validatedLaps($this->round($event, $number), 2);
        }

        $this->assertSame($onOneRound, $this->queriesOfTheResults());
    }

    private function queriesOfTheResults(): int
    {
        $queries = 0;

        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $this->get(route('home'));

        return $queries;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function closedEvent(array $attributes = []): Event
    {
        return Event::factory()->finished()->create([
            'lap_distance_meters' => 6000,
            'first_start_at' => CarbonImmutable::parse('2026-09-05 13:00:00'),
            'finished_at' => CarbonImmutable::parse('2026-09-06 04:04:00'),
            ...$attributes,
        ]);
    }

    private function round(Event $event, int $number): Round
    {
        return Round::factory()->numbered($number)->create(['event_id' => $event->getKey()]);
    }

    private function validatedLaps(Round $round, int $count): void
    {
        for ($runner = 0; $runner < $count; $runner++) {
            $this->lap($round, $this->runner($round->event), LapStatus::Validated);
        }
    }

    private function leavesTheRace(Round $round, ExitReason $reason): void
    {
        $this->lap($round, $this->runner($round->event, $reason), LapStatus::Eliminated);
    }

    private function leaveTheRace(Round $round, int $count, ExitReason $reason): void
    {
        for ($runner = 0; $runner < $count; $runner++) {
            $this->leavesTheRace($round, $reason);
        }
    }

    private function runner(Event $event, ?ExitReason $reason = null): Participant
    {
        $factory = Participant::factory()->confirmed();

        return ($reason === null ? $factory : $factory->outOfTheRace($reason))
            ->create(['event_id' => $event->getKey(), 'user_id' => User::factory()]);
    }

    private function lap(Round $round, Participant $runner, LapStatus $status): void
    {
        Lap::factory()->create([
            'round_id' => $round->getKey(),
            'participant_id' => $runner->getKey(),
            'status' => $status,
            'validated_at' => $status === LapStatus::Validated ? $round->starts_at->addMinutes(48) : null,
        ]);
    }

    private function winner(
        Event $event,
        string $firstName,
        int $laps,
        ?int $bibNumber = null,
        int $rank = 1,
    ): void {
        Standing::factory()->create([
            'event_id' => $event->getKey(),
            'participant_id' => $this->runner($event)->getKey(),
            'rank' => $rank,
            'bib_number' => $bibNumber,
            'first_name' => $firstName,
            'validated_laps' => $laps,
        ]);
    }
}
