<?php

namespace Tests\Feature\Race;

use App\Actions\OpenDueRounds;
use App\Models\Event;
use App\Models\Round;
use App\Models\User;
use App\Services\RaceSchedule\ResolveCurrentRound;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\RunsARace;
use Tests\TestCase;

class CurrentRoundTest extends TestCase
{
    use RefreshDatabase;
    use RunsARace;

    #[Test]
    public function it_shows_the_current_round_on_the_manage_page(): void
    {
        $this->travelTo($this->at('2026-09-05 17:30'));
        $this->runningEvent();

        $this->manage()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('manage/Index')
            ->where('currentRound.number', 5)
            ->where('currentRound.starts_at', '17:00')
            ->where('currentRound.deadline_at', '18:00'));
    }

    #[Test]
    public function it_shows_no_current_round_before_the_first_start(): void
    {
        $this->travelTo($this->at('2026-09-05 12:59'));
        $this->runningEvent();

        $this->manage()->assertInertia(
            fn (AssertableInertia $page) => $page->where('currentRound', null),
        );
    }

    #[Test]
    public function it_shows_no_current_round_while_the_event_is_not_running(): void
    {
        $this->travelTo($this->at('2026-09-05 17:30'));
        Event::factory()->registration()->create([
            'first_start_at' => $this->at('2026-09-05 13:00'),
            'lap_duration_minutes' => 60,
        ]);

        $this->manage()->assertInertia(
            fn (AssertableInertia $page) => $page->where('currentRound', null),
        );
    }

    #[Test]
    public function it_shows_no_current_round_once_the_event_is_finished(): void
    {
        $this->travelTo($this->at('2026-09-05 17:30'));
        Event::factory()->finished()->create([
            'first_start_at' => $this->at('2026-09-05 13:00'),
            'lap_duration_minutes' => 60,
        ]);

        $this->manage()->assertInertia(
            fn (AssertableInertia $page) => $page->where('currentRound', null),
        );
    }

    #[Test]
    public function it_shows_no_current_round_when_the_configuration_is_incomplete(): void
    {
        $this->travelTo($this->at('2026-09-05 17:30'));
        Event::factory()->running()->incomplete()->create();

        $this->manage()->assertInertia(
            fn (AssertableInertia $page) => $page->where('currentRound', null),
        );
    }

    #[Test]
    public function it_resolves_the_current_round_without_opening_it(): void
    {
        $this->travelTo($this->at('2026-09-05 17:30'));
        $event = $this->runningEvent();

        $round = app(ResolveCurrentRound::class)($event);

        $this->assertSame(5, $round?->number);
        $this->assertDatabaseCount('rounds', 0);
    }

    #[Test]
    public function it_resolves_the_round_the_opener_persists(): void
    {
        $this->travelTo($this->at('2026-09-05 17:30'));
        $event = $this->runningEvent();

        $round = app(ResolveCurrentRound::class)($event);
        app(OpenDueRounds::class)($event);

        $persisted = Round::query()->where('number', $round?->number)->sole();

        $this->assertSame($round?->startsAt->getTimestamp(), $persisted->starts_at->getTimestamp());
        $this->assertSame($round?->deadlineAt->getTimestamp(), $persisted->deadline_at->getTimestamp());
    }

    private function manage(): TestResponse
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        return $this->actingAs(User::factory()->manager()->create())
            ->get(route('manage.index'));
    }
}
