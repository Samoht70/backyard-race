<?php

namespace Tests\Feature\Manage;

use App\Enums\ExitReason;
use App\Enums\LapStatus;
use App\Models\Event;
use App\Models\Lap;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\RunsARace;
use Tests\TestCase;

class LapValidationTest extends TestCase
{
    use RefreshDatabase;
    use RunsARace;

    #[Test]
    public function it_validates_the_lap_the_manager_pressed(): void
    {
        $lap = $this->pendingLap();
        $this->travelTo($this->at('2026-09-05 13:47:32'));

        $response = $this->post(route('manage.laps.validate', $lap));

        $response->assertRedirect(route('manage.index'));
        $this->assertSame(LapStatus::Validated, $lap->refresh()->status);
        $this->assertSame('13:47:32', $lap->validated_at?->format('H:i:s'));
    }

    #[Test]
    public function it_ignores_a_validation_time_sent_by_the_client(): void
    {
        $lap = $this->pendingLap();
        $this->travelTo($this->at('2026-09-05 13:47:32'));

        $this->post(route('manage.laps.validate', $lap), [
            'validated_at' => '2026-09-05 13:10:00',
            'duration_seconds' => 12,
        ])->assertRedirect(route('manage.index'));

        $this->assertSame('13:47:32', $lap->refresh()->validated_at?->format('H:i:s'));
    }

    #[Test]
    public function it_says_nothing_when_the_manager_presses_a_second_time(): void
    {
        $lap = $this->pendingLap();
        $this->travelTo($this->at('2026-09-05 13:47:32'));
        $this->post(route('manage.laps.validate', $lap));

        $this->travelTo($this->at('2026-09-05 13:50:00'));
        $response = $this->post(route('manage.laps.validate', $lap));

        $response->assertRedirect(route('manage.index'));
        $response->assertSessionHasNoErrors();
        $this->assertSame('13:47:32', $lap->refresh()->validated_at?->format('H:i:s'));
    }

    #[Test]
    public function it_sends_the_manager_to_the_exceptional_correction_once_the_deadline_is_past(): void
    {
        $lap = $this->pendingLap();
        $this->travelTo($this->at('2026-09-05 14:00:01'));

        $response = $this->post(route('manage.laps.validate', $lap));

        $response->assertSessionHasErrors([
            'lap' => 'L’heure limite du tour est passée : cette boucle relève de la correction exceptionnelle.',
        ]);
        $this->assertSame(LapStatus::Pending, $lap->refresh()->status);
    }

    #[Test]
    public function it_refuses_a_runner_validating_their_own_lap(): void
    {
        $lap = $this->pendingLap();
        $this->travelTo($this->at('2026-09-05 13:47:32'));

        $response = $this->actingAs($lap->participant->user)
            ->post(route('manage.laps.validate', $lap));

        $response->assertForbidden();
        $this->assertSame(LapStatus::Pending, $lap->refresh()->status);
    }

    #[Test]
    public function it_refuses_to_validate_a_lap_the_runner_was_eliminated_on(): void
    {
        $lap = $this->pendingLap();
        $lap->update(['status' => LapStatus::Eliminated]);
        $this->travelTo($this->at('2026-09-05 13:47:32'));

        $response = $this->post(route('manage.laps.validate', $lap));

        $response->assertSessionHasErrors([
            'lap' => 'Ce coureur est sorti de la course : sa boucle ne se valide plus.',
        ]);
    }

    #[Test]
    public function it_lists_the_runners_of_the_current_round_with_their_readout(): void
    {
        $lap = $this->pendingLap();
        $this->travelTo($this->at('2026-09-05 13:47:32'));
        $this->post(route('manage.laps.validate', $lap));

        $response = $this->get(route('manage.index'));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('roundRunners', 1)
            ->where('roundRunners.0.lap_id', $lap->id)
            ->where('roundRunners.0.lap_status', LapStatus::Validated->value)
            ->where('roundRunners.0.validated_at', '13:47:32')
            ->where('roundRunners.0.duration_seconds', 2852)
            ->where('roundRunners.0.distance_meters', 6000)
            ->where('roundRunners.0.speed_kmh', 7.57)
            ->where('roundRunners.0.validated_laps', 1)
            ->etc());
    }

    #[Test]
    public function it_orders_the_board_by_bib_number(): void
    {
        $event = $this->racingEvent();
        $round = $this->roundOf($event);

        foreach ([27, 4, 15] as $bibNumber) {
            $runner = $this->runner($event);
            $runner->bib_number = $bibNumber;
            $runner->save();
            $round->laps()->create(['participant_id' => $runner->id]);
        }

        $this->travelTo($this->at('2026-09-05 13:30'));

        $this->get(route('manage.index'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('roundRunners.0.bib_label', '004')
            ->where('roundRunners.1.bib_label', '015')
            ->where('roundRunners.2.bib_label', '027')
            ->etc());
    }

    #[Test]
    public function it_drops_a_runner_eliminated_on_this_round_from_the_board(): void
    {
        $lap = $this->pendingLap();
        $lap->participant->leaveRace(ExitReason::Timeout, CarbonImmutable::now());
        $this->travelTo($this->at('2026-09-05 13:30'));

        $this->get(route('manage.index'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('roundRunners', [])
            ->where('tally.running', 0)
            ->where('tally.out', 1)
            ->etc());
    }

    #[Test]
    public function it_shows_an_empty_board_before_the_race_starts(): void
    {
        Event::factory()->registration()->create([
            'first_start_at' => $this->at('2026-09-05 13:00'),
            'lap_duration_minutes' => 60,
        ]);

        $this->get(route('manage.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('roundRunners', [])->etc());
    }

    private function racingEvent(): Event
    {
        return Event::factory()->running()->create([
            'first_start_at' => $this->at('2026-09-05 13:00'),
            'lap_duration_minutes' => 60,
            'lap_distance_meters' => 6000,
        ]);
    }

    private function pendingLap(): Lap
    {
        $event = $this->racingEvent();

        return $this->roundOf($event)->laps()->create([
            'participant_id' => $this->runner($event)->id,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->actingAs(User::factory()->manager()->create());
    }
}
