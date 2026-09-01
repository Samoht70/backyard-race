<?php

namespace Tests\Feature\Manage;

use App\Enums\EventStatus;
use App\Enums\ExitReason;
use App\Enums\Permission;
use App\Models\Event;
use App\Models\Lap;
use App\Models\Participant;
use App\Models\Round;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\RunsARace;
use Tests\TestCase;

class RaceDashboardTest extends TestCase
{
    use RefreshDatabase;
    use RunsARace;

    #[Test]
    public function it_shows_the_round_its_window_and_the_head_count(): void
    {
        $event = $this->racingEvent();
        $this->linedUp($this->roundOf($event, 6), 24);
        $this->outOfTheRace($event, 13);
        $this->travelTo($this->at('2026-09-05 18:30'));

        $this->get(route('manage.index'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('currentRound.number', 6)
            ->where('currentRound.starts_at', '18:00')
            ->where('currentRound.deadline_at', '19:00')
            ->where('tally.running', 24)
            ->where('tally.out', 13)
            ->has('roundRunners', 24)
            ->etc());
    }

    #[Test]
    public function it_keeps_the_head_count_when_a_lap_is_validated(): void
    {
        $event = $this->racingEvent();
        $laps = $this->linedUp($this->roundOf($event), 3);
        $this->travelTo($this->at('2026-09-05 13:40'));

        $this->post(route('manage.laps.validate', $laps->first()));

        $this->get(route('manage.index'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('tally.running', 3)
            ->where('tally.out', 0)
            ->has('roundRunners', 3)
            ->where('roundRunners.0.validated_at', '13:40:00')
            ->etc());
    }

    #[Test]
    public function it_reads_the_board_without_a_query_per_runner(): void
    {
        $event = $this->racingEvent();
        $round = $this->roundOf($event);
        $this->linedUp($round, 3);
        $this->travelTo($this->at('2026-09-05 13:40'));

        $this->get(route('manage.index'));

        $threeRunners = $this->queriesOfTheBoard();
        $this->linedUp($round, 27);

        $this->assertSame($threeRunners, $this->queriesOfTheBoard());
    }

    #[Test]
    public function it_holds_the_round_open_once_every_runner_of_it_is_out(): void
    {
        $event = $this->racingEvent();
        $this->linedUp($this->roundOf($event), 2);
        $this->roundOf($event, 2);
        $this->travelTo($this->at('2026-09-05 14:30'));

        $this->get(route('manage.index'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('currentRound.number', 2)
            ->where('roundRunners', [])
            ->where('tally.running', 2)
            ->etc());
    }

    #[Test]
    public function it_names_the_state_of_the_event_instead_of_an_empty_board(): void
    {
        Event::factory()->registration()->create([
            'first_start_at' => $this->at('2026-09-05 13:00'),
            'lap_duration_minutes' => 60,
        ]);

        $this->get(route('manage.index'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('eventStatus', EventStatus::Registration->value)
            ->where('currentRound', null)
            ->where('tally', null)
            ->where('roundRunners', [])
            ->etc());
    }

    #[Test]
    public function it_carries_no_event_status_until_an_event_exists(): void
    {
        $this->get(route('manage.index'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('eventStatus', null)
            ->etc());
    }

    #[Test]
    public function it_opens_the_screen_to_the_permission_that_validates_laps(): void
    {
        $this->racingEvent();
        $lapKeeper = User::factory()->create();
        $lapKeeper->givePermissionTo(Permission::ManageLaps->value);

        $this->actingAs($lapKeeper)->get(route('manage.index'))->assertOk();
    }

    #[Test]
    public function it_refuses_a_manager_of_everything_but_the_laps(): void
    {
        $this->racingEvent();
        $organiser = User::factory()->create();
        $organiser->givePermissionTo(Permission::ManageEvent->value);

        $this->actingAs($organiser)->get(route('manage.index'))->assertForbidden();
    }

    private function queriesOfTheBoard(): int
    {
        $queries = 0;

        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $this->get(route('manage.index'));

        return $queries;
    }

    /**
     * @return Collection<int, Lap>
     */
    private function linedUp(Round $round, int $count): Collection
    {
        return $round->laps()->createMany(
            $this->runners($round->event, $count)
                ->map(fn (Participant $runner): array => ['participant_id' => $runner->id])
                ->all(),
        );
    }

    private function outOfTheRace(Event $event, int $count): void
    {
        $this->runners($event, $count)->each(
            fn (Participant $runner) => $runner->leaveRace(ExitReason::Timeout, $this->at('2026-09-05 17:00')),
        );
    }

    private function racingEvent(): Event
    {
        return Event::factory()->running()->create([
            'first_start_at' => $this->at('2026-09-05 13:00'),
            'lap_duration_minutes' => 60,
            'lap_distance_meters' => 6000,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->actingAs(User::factory()->manager()->create());
    }
}
