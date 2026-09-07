<?php

namespace Tests\Feature\Race;

use App\Enums\ExitReason;
use App\Models\Event;
use App\Models\Participant;
use App\Models\Standing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StandingsPageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_frozen_standings_to_a_guest(): void
    {
        $event = Event::factory()->finished()->create(['lap_distance_meters' => 6000]);
        $this->standing($event, rank: 1, firstName: 'Thomas', laps: 18);
        $this->standing($event, rank: 2, firstName: 'Paul', laps: 17, exitReason: ExitReason::Timeout);

        $this->get(route('standings.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->component('Standings')
                ->where('standings.0.first_name', 'Thomas')
                ->where('standings.0.rank', 1)
                ->where('standings.0.validated_laps', 18)
                ->where('standings.0.covered_meters', 108000)
                ->where('standings.0.status', 'finished')
                ->where('standings.1.first_name', 'Paul')
                ->where('standings.1.status', 'eliminated')
            );
    }

    #[Test]
    public function it_orders_the_lines_by_rank_then_bib(): void
    {
        $event = Event::factory()->finished()->create();
        $this->standing($event, rank: 3, firstName: 'Troisième', laps: 9, bibNumber: 12);
        $this->standing($event, rank: 1, firstName: 'Second', laps: 12, bibNumber: 41);
        $this->standing($event, rank: 1, firstName: 'Premier', laps: 12, bibNumber: 7);

        $this->get(route('standings.index'))
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->where('standings.0.first_name', 'Premier')
                ->where('standings.0.bib_label', '007')
                ->where('standings.1.first_name', 'Second')
                ->where('standings.2.first_name', 'Troisième')
            );
    }

    #[Test]
    public function it_serves_an_empty_standing_when_nobody_was_classed(): void
    {
        Event::factory()->finished()->create();

        $this->get(route('standings.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->component('Standings')
                ->where('standings', [])
            );
    }

    #[Test]
    public function it_hides_the_page_while_the_race_is_still_running(): void
    {
        Event::factory()->running()->create();

        $this->get(route('standings.index'))->assertNotFound();
    }

    #[Test]
    public function it_hides_the_page_when_no_event_exists(): void
    {
        $this->get(route('standings.index'))->assertNotFound();
    }

    #[Test]
    public function it_offers_the_standings_in_the_navigation_only_once_the_race_is_closed(): void
    {
        $event = Event::factory()->running()->create();

        $this->get(route('home'))
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->where('access.standings', false)
            );

        $event->forceFill(['status' => 'finished'])->save();

        $this->get(route('home'))
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->where('access.standings', true)
            );
    }

    private function standing(
        Event $event,
        int $rank,
        string $firstName,
        int $laps,
        ?int $bibNumber = null,
        ?ExitReason $exitReason = null,
    ): void {
        Standing::factory()->create([
            'event_id' => $event->getKey(),
            'participant_id' => Participant::factory()->confirmed()->create([
                'event_id' => $event->getKey(),
                'user_id' => User::factory(),
            ])->getKey(),
            'rank' => $rank,
            'bib_number' => $bibNumber,
            'first_name' => $firstName,
            'validated_laps' => $laps,
            'exit_reason' => $exitReason,
        ]);
    }
}
