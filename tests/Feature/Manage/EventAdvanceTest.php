<?php

namespace Tests\Feature\Manage;

use App\Actions\AdvanceEventStatus;
use App\Enums\EventStatus;
use App\Enums\Permission;
use App\Exceptions\EventTransitionRefusedException;
use App\Models\Event;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EventAdvanceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_opens_the_registrations(): void
    {
        $this->assertAdvances(EventStatus::Draft, EventStatus::Registration);
    }

    #[Test]
    public function it_starts_the_race(): void
    {
        $this->assertAdvances(EventStatus::Registration, EventStatus::Running);
    }

    #[Test]
    public function it_finishes_the_race(): void
    {
        $this->assertAdvances(EventStatus::Running, EventStatus::Finished);
    }

    #[Test]
    public function it_refuses_to_skip_from_draft_to_running(): void
    {
        $this->assertRefuses(EventStatus::Draft, EventStatus::Running);
    }

    #[Test]
    public function it_refuses_to_go_back_from_running_to_registration(): void
    {
        $this->assertRefuses(EventStatus::Running, EventStatus::Registration);
    }

    #[Test]
    public function it_refuses_to_advance_a_finished_event(): void
    {
        $this->assertRefuses(EventStatus::Finished, EventStatus::Finished);
    }

    #[Test]
    public function it_refuses_to_start_without_a_first_start_time_and_names_the_reason(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->registration()->create(['first_start_at' => null]);

        $response = $this->actingAs($this->manager())
            ->post(route('manage.event.advance'), ['to' => EventStatus::Running->value]);

        $response->assertSessionHasErrors([
            'to' => 'L’heure du premier départ n’est pas renseignée.',
        ]);

        $this->assertSame(EventStatus::Registration, Event::query()->sole()->status);
    }

    #[Test]
    public function it_names_every_missing_field_at_once(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->registration()->incomplete()->create();

        $this->actingAs($this->manager())
            ->post(route('manage.event.advance'), ['to' => EventStatus::Running->value])
            ->assertSessionHasErrors('to');

        $errors = session('errors')?->get('to') ?? [];

        $this->assertCount(3, $errors);
    }

    #[Test]
    public function it_refuses_to_advance_an_event_someone_else_already_moved(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $event = Event::factory()->create();
        $advance = app(AdvanceEventStatus::class);

        Event::query()->whereKey($event->getKey())
            ->update(['status' => EventStatus::Registration->value]);

        $this->expectException(EventTransitionRefusedException::class);

        $advance($event, EventStatus::Registration);
    }

    #[Test]
    public function it_refuses_a_participant(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->create();

        $this->actingAs(User::factory()->participant()->create())
            ->post(route('manage.event.advance'), ['to' => EventStatus::Registration->value])
            ->assertForbidden();
    }

    #[Test]
    public function it_refuses_an_unknown_target_status(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->create();

        $this->actingAs($this->manager())
            ->post(route('manage.event.advance'), ['to' => 'cancelled'])
            ->assertSessionHasErrors('to');
    }

    #[Test]
    public function it_reserves_closing_the_race_to_the_finish_event_ability(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->running()->create();

        $user = User::factory()->create();
        $user->givePermissionTo(Permission::ManageEvent->value);

        $this->actingAs($user)
            ->post(route('manage.event.advance'), ['to' => EventStatus::Finished->value])
            ->assertForbidden();

        $this->assertSame(EventStatus::Running, Event::query()->sole()->status);
    }

    #[Test]
    public function it_flashes_the_new_status_to_the_manager(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->create();

        $this->actingAs($this->manager())
            ->post(route('manage.event.advance'), ['to' => EventStatus::Registration->value])
            ->assertSessionHas(
                'inertia.flash_data.toast.message',
                'L’événement est passé en « Inscriptions ouvertes ».',
            );
    }

    private function assertAdvances(EventStatus $from, EventStatus $to): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->create(['status' => $from]);

        $this->actingAs($this->manager())
            ->post(route('manage.event.advance'), ['to' => $to->value])
            ->assertRedirect(route('manage.event.edit'));

        $this->assertSame($to, Event::query()->sole()->status);
    }

    private function assertRefuses(EventStatus $from, EventStatus $to): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->create(['status' => $from]);

        $response = $this->actingAs($this->manager())
            ->post(route('manage.event.advance'), ['to' => $to->value]);

        if ($from === EventStatus::Finished) {
            $response->assertForbidden();
        } else {
            $response->assertSessionHasErrors('to');
        }

        $this->assertSame($from, Event::query()->sole()->status);
    }

    private function manager(): User
    {
        return User::factory()->manager()->create();
    }
}
