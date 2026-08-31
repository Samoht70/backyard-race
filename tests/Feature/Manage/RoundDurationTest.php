<?php

namespace Tests\Feature\Manage;

use App\Actions\OpenDueRounds;
use App\Enums\ScheduleChange;
use App\Models\Event;
use App\Models\Round;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\RunsARace;
use Tests\TestCase;

class RoundDurationTest extends TestCase
{
    use RefreshDatabase;
    use RunsARace;

    #[Test]
    public function it_records_the_change_the_manager_asks_for(): void
    {
        $this->travelTo($this->at('2026-09-05 14:30'));
        $this->runningEvent();

        $response = $this->post(route('manage.rounds.duration'), [
            'from' => 3,
            'lap_duration_minutes' => 55,
            'change' => ScheduleChange::Onwards->value,
        ]);

        $response->assertRedirect(route('manage.index'));
        $this->assertDatabaseHas('schedule_segments', ['from_round_number' => 3, 'lap_duration_minutes' => 55]);
    }

    #[Test]
    public function it_writes_the_two_rows_of_a_single_round_change(): void
    {
        $this->travelTo($this->at('2026-09-05 14:30'));
        $this->runningEvent();

        $this->post(route('manage.rounds.duration'), [
            'from' => 3,
            'lap_duration_minutes' => 55,
            'change' => ScheduleChange::SingleRound->value,
        ])->assertRedirect(route('manage.index'));

        $this->assertDatabaseHas('schedule_segments', ['from_round_number' => 3, 'lap_duration_minutes' => 55]);
        $this->assertDatabaseHas('schedule_segments', ['from_round_number' => 4, 'lap_duration_minutes' => 60]);
    }

    #[Test]
    public function it_tells_the_manager_a_started_round_is_out_of_reach(): void
    {
        $this->travelTo($this->at('2026-09-05 15:30'));
        $event = $this->runningEvent();
        app(OpenDueRounds::class)($event);

        $response = $this->post(route('manage.rounds.duration'), [
            'from' => 3,
            'lap_duration_minutes' => 55,
            'change' => ScheduleChange::Onwards->value,
        ]);

        $response->assertSessionHasErrors([
            'from' => 'Ce tour est déjà parti : sa durée n’est plus modifiable.',
        ]);
        $this->assertDatabaseCount('schedule_segments', 0);
    }

    #[Test]
    public function it_refuses_a_round_the_scheduler_has_not_opened_yet(): void
    {
        $this->travelTo($this->at('2026-09-05 13:30'));
        $event = $this->runningEvent();
        app(OpenDueRounds::class)($event);
        $this->travelTo($this->at('2026-09-05 14:00:40'));

        $response = $this->post(route('manage.rounds.duration'), [
            'from' => 2,
            'lap_duration_minutes' => 55,
            'change' => ScheduleChange::Onwards->value,
        ]);

        $response->assertSessionHasErrors('from');
        $this->assertSame([1, 2], Round::query()->orderBy('number')->pluck('number')->all());
        $this->assertDatabaseCount('schedule_segments', 0);
    }

    #[Test]
    public function it_refuses_a_duration_outside_the_configuration_bounds(): void
    {
        $this->travelTo($this->at('2026-09-05 14:30'));
        $this->runningEvent();

        $this->post(route('manage.rounds.duration'), [
            'from' => 3,
            'lap_duration_minutes' => 0,
            'change' => ScheduleChange::Onwards->value,
        ])->assertSessionHasErrors('lap_duration_minutes');

        $this->post(route('manage.rounds.duration'), [
            'from' => 3,
            'lap_duration_minutes' => 1441,
            'change' => ScheduleChange::Onwards->value,
        ])->assertSessionHasErrors('lap_duration_minutes');

        $this->assertDatabaseCount('schedule_segments', 0);
    }

    #[Test]
    public function it_refuses_a_runner(): void
    {
        $this->travelTo($this->at('2026-09-05 14:30'));
        $this->runningEvent();

        $response = $this->actingAs(User::factory()->participant()->create())
            ->post(route('manage.rounds.duration'), [
                'from' => 3,
                'lap_duration_minutes' => 55,
                'change' => ScheduleChange::Onwards->value,
            ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('schedule_segments', 0);
    }

    #[Test]
    public function it_offers_the_next_round_on_the_manage_screen(): void
    {
        $this->travelTo($this->at('2026-09-05 14:30'));
        $event = $this->runningEvent();
        app(OpenDueRounds::class)($event);

        $response = $this->get(route('manage.index'));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('nextRound.number', 3)
            ->where('nextRound.starts_at', '15:00')
            ->where('nextRound.lap_duration_minutes', 60)
            ->etc());
    }

    #[Test]
    public function it_offers_the_first_round_before_the_race_starts(): void
    {
        Event::factory()->registration()->create([
            'first_start_at' => $this->at('2026-09-05 13:00'),
            'lap_duration_minutes' => 60,
        ]);

        $response = $this->get(route('manage.index'));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('nextRound.number', 1)
            ->where('nextRound.starts_at', '13:00')
            ->etc());
    }

    #[Test]
    public function it_offers_nothing_when_the_event_has_no_grid(): void
    {
        Event::factory()->running()->incomplete()->create();

        $response = $this->get(route('manage.index'));

        $response->assertInertia(fn (AssertableInertia $page) => $page->where('nextRound', null)->etc());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->actingAs(User::factory()->manager()->create());
    }
}
