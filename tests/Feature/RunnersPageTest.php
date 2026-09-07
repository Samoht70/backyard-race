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

class RunnersPageTest extends TestCase
{
    use RefreshDatabase;
    use RunsARace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    #[Test]
    public function it_redirects_a_guest_to_the_login_page(): void
    {
        $this->runningEvent();

        $this->get(route('runners'))->assertRedirect(route('login'));
    }

    #[Test]
    public function it_shows_the_counters_and_no_runner_before_any_search(): void
    {
        $event = $this->runningEvent();
        $this->runner($event);
        $this->outOfTheRace($event, ExitReason::Timeout);

        $this->actingAsParticipant()
            ->get(route('runners'))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('Runners')
                    ->where('query', null)
                    ->where('tally.running', 1)
                    ->where('tally.out', 1)
                    ->where('runners', []),
            );
    }

    #[Test]
    public function it_launches_no_search_for_a_single_letter(): void
    {
        $event = $this->runningEvent();
        $this->named($event, 'Marchand', 'Yves', 12);

        $this->actingAsParticipant()
            ->get(route('runners', ['q' => 'm']))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('query', null)
                    ->where('runners', []),
            );
    }

    #[Test]
    public function it_finds_a_runner_by_a_partial_name_regardless_of_case(): void
    {
        $event = $this->runningEvent();
        $this->named($event, 'Marchand', 'Yves', 12);

        $this->actingAsParticipant()
            ->get(route('runners', ['q' => 'mar']))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('query', 'mar')
                    ->has('runners', 1)
                    ->where('runners.0.first_name', 'Yves')
                    ->where('runners.0.last_name', 'Marchand'),
            );
    }

    #[Test]
    public function it_finds_a_bib_number_by_equality_only(): void
    {
        $event = $this->runningEvent();
        $this->named($event, 'Marchand', 'Yves', 12);
        $this->named($event, 'Autre', 'Coureur', 112);

        $this->actingAsParticipant()
            ->get(route('runners', ['q' => '12']))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->has('runners', 1)
                    ->where('runners.0.bib_label', '012'),
            );
    }

    #[Test]
    public function it_reports_the_status_the_lap_count_and_the_exit_time_of_an_eliminated_runner(): void
    {
        $event = $this->runningEvent('2026-09-05 13:00', 60);
        $runner = $this->named($event, 'Dubois', 'Léa', 5);
        $this->validateLaps($event, $runner, 2);
        $runner->leaveRace(ExitReason::Timeout, $this->at('2026-09-05 15:12'));

        $this->actingAsParticipant()
            ->get(route('runners', ['q' => 'dubois']))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('runners.0.status', 'eliminated')
                    ->where('runners.0.validated_laps', 2)
                    ->where('runners.0.exited_at', '15:12')
                    ->where('runners.0.covered_meters', 2 * $event->lap_distance_meters)
                    ->where('runners.0.last_validated_round', 2),
            );
    }

    #[Test]
    public function it_never_exposes_personal_data_or_a_ranking_field(): void
    {
        $event = $this->runningEvent();
        $this->named($event, 'Marchand', 'Yves', 12);

        $this->actingAsParticipant()
            ->get(route('runners', ['q' => 'mar']))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->missing('runners.0.phone')
                    ->missing('runners.0.birth_date')
                    ->missing('runners.0.pps_number')
                    ->missing('runners.0.emergency_contact_name')
                    ->missing('runners.0.emergency_contact_phone')
                    ->missing('runners.0.rank'),
            );
    }

    #[Test]
    public function it_ignores_registrations_that_are_not_confirmed(): void
    {
        $event = $this->runningEvent();
        Participant::factory()->for(User::factory()->state(['first_name' => 'Yves', 'last_name' => 'Marchand']))
            ->create(['event_id' => $event->getKey()]);

        $this->actingAsParticipant()
            ->get(route('runners', ['q' => 'marchand']))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('runners', []));
    }

    #[Test]
    public function it_reports_no_match_for_a_search_that_finds_nobody(): void
    {
        $event = $this->runningEvent();
        $this->named($event, 'Marchand', 'Yves', 12);

        $this->actingAsParticipant()
            ->get(route('runners', ['q' => 'zzz']))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('query', 'zzz')
                    ->where('runners', []),
            );
    }

    #[Test]
    public function it_keeps_the_tally_as_a_full_roster_count_regardless_of_the_search(): void
    {
        $event = $this->runningEvent();
        $this->named($event, 'Marchand', 'Yves', 12);
        $this->named($event, 'Petit', 'Hugo', 7);
        $this->named($event, 'Petit', 'Zoé', 8)->leaveRace(ExitReason::Withdrawal, $this->at('2026-09-05 15:00'));

        $this->actingAsParticipant()
            ->get(route('runners', ['q' => 'mar']))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->has('runners', 1)
                    ->where('tally.running', 2)
                    ->where('tally.out', 1),
            );
    }

    #[Test]
    public function it_responds_404_without_any_event(): void
    {
        $this->actingAsParticipant()
            ->get(route('runners'))
            ->assertNotFound();
    }

    #[Test]
    public function it_refuses_a_draft_event_to_a_participant(): void
    {
        Event::factory()->create();

        $this->actingAsParticipant()
            ->get(route('runners'))
            ->assertForbidden();
    }

    private function actingAsParticipant(): static
    {
        return $this->actingAs(User::factory()->participant()->create());
    }

    private function named(Event $event, string $lastName, string $firstName, int $bib): Participant
    {
        return Participant::factory()
            ->confirmed()
            ->withBib($bib)
            ->for(User::factory()->state(['first_name' => $firstName, 'last_name' => $lastName]))
            ->create(['event_id' => $event->getKey()]);
    }

    private function validateLaps(Event $event, Participant $runner, int $count): void
    {
        for ($number = 1; $number <= $count; $number++) {
            Lap::factory()->validated()->for($this->roundOf($event, $number))->for($runner)->create();
        }
    }

    private function outOfTheRace(Event $event, ExitReason $reason): Participant
    {
        return Participant::factory()
            ->confirmed()
            ->outOfTheRace($reason)
            ->create(['event_id' => $event->getKey()]);
    }
}
