<?php

namespace Tests\Feature;

use App\Enums\ExitReason;
use App\Models\Event;
use App\Models\Lap;
use App\Models\Participant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\RunsARace;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;
    use RunsARace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    #[Test]
    public function it_sends_a_guest_to_the_login_page(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    #[Test]
    public function it_still_answers_when_no_event_exists(): void
    {
        $this->actingAs(User::factory()->participant()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('Dashboard')
                    ->where('mode', 'no_event')
                    ->where('event', null),
            );
    }

    #[Test]
    public function it_prompts_the_manager_toward_the_console_before_the_race_starts(): void
    {
        Event::factory()->registration()->create();

        $this->actingAs(User::factory()->manager()->create())
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('mode', 'manager_idle'));
    }

    #[Test]
    public function it_offers_the_manager_a_search_once_the_race_is_running(): void
    {
        $event = $this->runningEvent();
        $this->named($event, 'Marchand', 'Yves', 12);

        $this->actingAs(User::factory()->manager()->create())
            ->get(route('dashboard', ['q' => 'mar']))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('mode', 'manager_search')
                    ->where('query', 'mar')
                    ->has('runners', 1)
                    ->where('runners.0.last_name', 'Marchand'),
            );
    }

    #[Test]
    public function it_gives_the_manager_search_precedence_over_their_own_registration(): void
    {
        $event = $this->runningEvent();
        $manager = User::factory()->manager()->create();
        Participant::factory()->confirmed()->for($manager)->create(['event_id' => $event->getKey()]);

        $this->actingAs($manager)
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('mode', 'manager_search'));
    }

    #[Test]
    public function it_prompts_an_unregistered_account_to_register(): void
    {
        Event::factory()->registration()->create();

        $this->actingAs(User::factory()->participant()->create())
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('mode', 'no_registration'));
    }

    #[Test]
    public function it_points_a_registered_runner_to_the_registration_tab_before_the_race_starts(): void
    {
        $event = Event::factory()->registration()->create();
        $runner = User::factory()->participant()->create();
        Participant::factory()->confirmed()->for($runner)->create(['event_id' => $event->getKey()]);

        $this->actingAs($runner)
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('mode', 'runner_waiting'));
    }

    #[Test]
    public function it_keeps_a_pending_registration_off_the_race_view_even_while_running(): void
    {
        $event = $this->runningEvent();
        $runner = User::factory()->participant()->create();
        Participant::factory()->for($runner)->create(['event_id' => $event->getKey()]);

        $this->actingAs($runner)
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('mode', 'runner_waiting'));
    }

    #[Test]
    public function it_keeps_a_cancelled_registration_off_the_race_view_even_while_running(): void
    {
        $event = $this->runningEvent();
        $runner = User::factory()->participant()->create();
        Participant::factory()->cancelled()->for($runner)->create(['event_id' => $event->getKey()]);

        $this->actingAs($runner)
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('mode', 'runner_waiting'));
    }

    #[Test]
    public function it_shows_a_confirmed_runners_own_race_status_once_the_race_is_running(): void
    {
        $event = $this->runningEvent('2026-09-05 13:00', 60);
        $runner = $this->named($event, 'Dubois', 'Léa', 5);
        Lap::factory()->validated()->for($this->roundOf($event, 1))->for($runner)->create();

        $this->actingAs($runner->user)
            ->get(route('dashboard'))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('mode', 'runner_active')
                    ->where('runner.first_name', 'Léa')
                    ->where('runner.last_name', 'Dubois')
                    ->where('runner.status', 'running')
                    ->where('runner.validated_laps', 1)
                    ->has('runner.laps', 1)
                    ->where('runner.laps.0.round_number', 1),
            );
    }

    #[Test]
    public function it_lists_a_runners_laps_from_round_one_to_the_current_one(): void
    {
        $event = $this->racingEvent();
        $runner = $this->named($event, 'Marchand', 'Yves', 12);
        Lap::factory()
            ->validated($this->at('2026-09-05 13:47:32'))
            ->for($this->roundOf($event, 1))
            ->for($runner)
            ->create();
        $this->roundOf($event, 2)->laps()->create(['participant_id' => $runner->id]);

        $this->actingAs(User::factory()->manager()->create())
            ->get(route('dashboard', ['q' => 'mar']))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->has('runners.0.laps', 2)
                    ->where('runners.0.laps.0.round_number', 1)
                    ->where('runners.0.laps.0.duration_seconds', 2852)
                    ->where('runners.0.laps.0.distance_meters', 6000)
                    ->where('runners.0.laps.0.speed_kmh', 7.57)
                    ->where('runners.0.laps.0.corrected', false)
                    ->where('runners.0.laps.1.round_number', 2)
                    ->where('runners.0.laps.1.duration_seconds', null)
                    ->where('runners.0.laps.1.speed_kmh', null)
                    ->etc(),
            );
    }

    #[Test]
    public function it_flags_a_corrected_lap_in_the_search_result(): void
    {
        $event = $this->racingEvent();
        $runner = $this->named($event, 'Marchand', 'Yves', 12);
        Lap::factory()
            ->validated($this->at('2026-09-05 13:47:32'))
            ->corrected()
            ->for($this->roundOf($event, 1))
            ->for($runner)
            ->create();

        $this->actingAs(User::factory()->manager()->create())
            ->get(route('dashboard', ['q' => 'mar']))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('runners.0.laps.0.corrected', true)
                    ->etc(),
            );
    }

    #[Test]
    public function it_exposes_the_current_pending_lap_for_a_runner_still_racing(): void
    {
        $event = $this->racingEvent();
        $runner = $this->named($event, 'Marchand', 'Yves', 12);
        $lap = $this->roundOf($event)->laps()->create(['participant_id' => $runner->id]);

        $this->actingAs(User::factory()->manager()->create())
            ->get(route('dashboard', ['q' => 'mar']))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('runners.0.pending_lap_id', $lap->id)
                    ->etc(),
            );
    }

    #[Test]
    public function it_clears_the_pending_lap_once_a_runner_has_left_the_race(): void
    {
        $event = $this->racingEvent();
        $runner = $this->named($event, 'Marchand', 'Yves', 12);
        $this->roundOf($event)->laps()->create(['participant_id' => $runner->id]);
        $runner->leaveRace(ExitReason::Withdrawal, $this->at('2026-09-05 13:10'));

        $this->actingAs(User::factory()->manager()->create())
            ->get(route('dashboard', ['q' => 'mar']))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('runners.0.pending_lap_id', null)
                    ->etc(),
            );
    }

    #[Test]
    public function it_carries_the_running_round_and_the_head_count_for_the_manager(): void
    {
        $event = $this->racingEvent();
        $this->roundOf($event, 6);
        $this->runners($event, 4);
        $this->outOfTheRace($event, 3);
        $this->travelTo($this->at('2026-09-05 18:30'));

        $this->actingAs(User::factory()->manager()->create())
            ->get(route('dashboard'))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('currentRound.number', 6)
                    ->where('currentRound.starts_at', '18:00')
                    ->where('currentRound.deadline_at', '19:00')
                    ->where('tally.running', 4)
                    ->where('tally.out', 3)
                    ->etc(),
            );
    }

    #[Test]
    public function it_carries_the_running_round_and_the_head_count_for_a_runner(): void
    {
        $event = $this->runningEvent('2026-09-05 13:00', 60);
        $runner = $this->named($event, 'Dubois', 'Léa', 5);
        $this->roundOf($event, 2);
        $this->outOfTheRace($event, 2);
        $this->travelTo($this->at('2026-09-05 14:30'));

        $this->actingAs($runner->user)
            ->get(route('dashboard'))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('mode', 'runner_active')
                    ->where('currentRound.number', 2)
                    ->where('tally.running', 1)
                    ->where('tally.out', 2)
                    ->etc(),
            );
    }

    #[Test]
    public function it_carries_no_round_before_the_race_starts(): void
    {
        Event::factory()->registration()->create([
            'first_start_at' => $this->at('2026-09-05 13:00'),
            'lap_duration_minutes' => 60,
        ]);

        $this->actingAs(User::factory()->manager()->create())
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page->missing('currentRound'));
    }

    #[Test]
    public function it_carries_no_round_when_the_event_has_no_grid(): void
    {
        Event::factory()->running()->incomplete()->create();

        $this->actingAs(User::factory()->manager()->create())
            ->get(route('dashboard'))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('mode', 'manager_search')
                    ->where('currentRound', null)
                    ->etc(),
            );
    }

    #[Test]
    public function it_reports_an_eliminated_runners_own_status_and_exit_time(): void
    {
        $event = $this->runningEvent('2026-09-05 13:00', 60);
        $runner = $this->named($event, 'Dubois', 'Léa', 5);
        $runner->leaveRace(ExitReason::Timeout, $this->at('2026-09-05 15:12'));

        $this->actingAs($runner->user)
            ->get(route('dashboard'))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('mode', 'runner_active')
                    ->where('runner.status', 'eliminated')
                    ->where('runner.exited_at', '15:12'),
            );
    }

    #[Test]
    public function it_no_longer_carries_registration_lifecycle_details_on_the_home_screen(): void
    {
        $event = $this->runningEvent();
        $runner = $this->named($event, 'Dubois', 'Léa', 5);

        $this->actingAs($runner->user)
            ->get(route('dashboard'))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->missing('registration')
                    ->missing('runner.phone')
                    ->missing('runner.pps_number'),
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

    private function named(Event $event, string $lastName, string $firstName, int $bib): Participant
    {
        return Participant::factory()
            ->confirmed()
            ->withBib($bib)
            ->for(User::factory()->state(['first_name' => $firstName, 'last_name' => $lastName]))
            ->create(['event_id' => $event->getKey()]);
    }
}
