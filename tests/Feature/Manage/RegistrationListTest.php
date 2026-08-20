<?php

namespace Tests\Feature\Manage;

use App\Enums\Permission;
use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\Participant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegistrationListTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_redirects_a_guest_to_the_login_page(): void
    {
        $this->get(route('manage.registrations.index'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function it_refuses_a_participant(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->actingAs(User::factory()->participant()->create())
            ->get(route('manage.registrations.index'))
            ->assertForbidden();
    }

    #[Test]
    public function it_refuses_a_manager_who_only_carries_the_event_ability(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->givePermissionTo(Permission::ManageEvent->value);

        $this->actingAs($user)
            ->get(route('manage.registrations.index'))
            ->assertForbidden();
    }

    #[Test]
    public function it_admits_a_manager_carrying_only_the_participants_ability(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->givePermissionTo(Permission::ManageParticipants->value);

        $this->actingAs($user)
            ->get(route('manage.registrations.index'))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page->component('manage/registrations/Index'),
            );
    }

    #[Test]
    public function it_lists_every_registration_sorted_by_name(): void
    {
        $event = $this->openEvent();
        $this->register($event, 'Zoé', 'Ancel', RegistrationStatus::Pending);
        $this->register($event, 'Adrien', 'Zeller', RegistrationStatus::Confirmed);

        $this->actingAs($this->manager())
            ->get(route('manage.registrations.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('registrations', 2)
                ->where('registrations.0.last_name', 'Ancel')
                ->where('registrations.1.last_name', 'Zeller'));
    }

    #[Test]
    public function it_filters_the_list_by_status(): void
    {
        $event = $this->openEvent();
        $this->register($event, 'Zoé', 'Ancel', RegistrationStatus::Pending);
        $this->register($event, 'Adrien', 'Zeller', RegistrationStatus::Confirmed);

        $this->actingAs($this->manager())
            ->get(route('manage.registrations.index', ['status' => RegistrationStatus::Confirmed->value]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('registrations', 1)
                ->where('registrations.0.last_name', 'Zeller')
                ->where('status', RegistrationStatus::Confirmed->value));
    }

    /** A trafficked query string on a navigation must land on the list, not on an error page. */
    #[Test]
    public function it_falls_back_to_every_registration_on_an_unknown_status(): void
    {
        $event = $this->openEvent();
        $this->register($event, 'Zoé', 'Ancel', RegistrationStatus::Pending);

        $this->actingAs($this->manager())
            ->get(route('manage.registrations.index', ['status' => 'archived']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('registrations', 1)
                ->where('status', null));
    }

    #[Test]
    public function it_counts_the_registrations_per_status(): void
    {
        $event = $this->openEvent();
        $this->register($event, 'Zoé', 'Ancel', RegistrationStatus::Pending);
        $this->register($event, 'Adrien', 'Zeller', RegistrationStatus::Confirmed);
        $this->register($event, 'Marie', 'Bloch', RegistrationStatus::Cancelled);

        $this->actingAs($this->manager())
            ->get(route('manage.registrations.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('counts.all', 3)
                ->where('counts.pending', 1)
                ->where('counts.confirmed', 1)
                ->where('counts.cancelled', 1));
    }

    #[Test]
    public function it_shows_the_confirmed_seat_count(): void
    {
        $event = $this->openEvent(['max_participants' => 40]);
        $this->register($event, 'Adrien', 'Zeller', RegistrationStatus::Confirmed);
        $this->register($event, 'Zoé', 'Ancel', RegistrationStatus::Pending);

        $this->actingAs($this->manager())
            ->get(route('manage.registrations.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('seats.confirmed', 1)
                ->where('seats.capacity', 40));
    }

    #[Test]
    public function it_exposes_the_allowed_transitions_of_each_row(): void
    {
        $event = $this->openEvent();
        $this->register($event, 'Zoé', 'Ancel', RegistrationStatus::Pending);

        $this->actingAs($this->manager())
            ->get(route('manage.registrations.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('registrations.0.allowed_transitions', ['confirm', 'cancel']));
    }

    #[Test]
    public function it_names_the_refusal_when_the_event_is_full(): void
    {
        $event = $this->openEvent(['max_participants' => 1]);
        $this->register($event, 'Adrien', 'Zeller', RegistrationStatus::Confirmed);

        $this->actingAs($this->manager())
            ->get(route('manage.registrations.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('refusals', 1));
    }

    #[Test]
    public function it_names_no_refusal_when_seats_are_left(): void
    {
        $this->openEvent(['max_participants' => 40]);

        $this->actingAs($this->manager())
            ->get(route('manage.registrations.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('refusals', 0));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function openEvent(array $attributes = []): Event
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        return Event::factory()->registration()->create($attributes);
    }

    private function register(Event $event, string $firstName, string $lastName, RegistrationStatus $status): void
    {
        Participant::factory()->create([
            'event_id' => $event->id,
            'user_id' => User::factory()->participant()->create([
                'first_name' => $firstName,
                'last_name' => $lastName,
            ])->id,
            'status' => $status,
        ]);
    }

    private function manager(): User
    {
        return User::factory()->manager()->create();
    }
}
