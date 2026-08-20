<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Models\Event;
use App\Models\Participant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\RegistersRunners;
use Tests\TestCase;

class SharedAccessTest extends TestCase
{
    use RefreshDatabase, RegistersRunners;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    #[Test]
    public function it_closes_every_area_to_a_guest(): void
    {
        $this->openEvent();

        $this->get(route('home'))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('access', [
                        'event' => false,
                        'documents' => false,
                        'registration' => false,
                    ]),
            );
    }

    #[Test]
    public function it_hides_the_briefing_and_the_documents_from_a_participant_while_the_event_is_a_draft(): void
    {
        Event::factory()->create();

        $this->actingAs(User::factory()->participant()->create())
            ->get(route('dashboard'))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('access.event', false)
                    ->where('access.documents', false),
            );
    }

    #[Test]
    public function it_opens_the_briefing_and_the_documents_once_the_event_takes_registrations(): void
    {
        $this->openEvent();

        $this->actingAs(User::factory()->participant()->create())
            ->get(route('dashboard'))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('access.event', true)
                    ->where('access.documents', true),
            );
    }

    #[Test]
    public function it_lets_the_event_manager_through_a_draft(): void
    {
        Event::factory()->create();

        $this->actingAs(User::factory()->manager()->create())
            ->get(route('dashboard'))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('access.event', true)
                    ->where('access.documents', true),
            );
    }

    #[Test]
    public function it_keeps_a_draft_shut_for_a_document_manager_who_cannot_manage_the_event(): void
    {
        Event::factory()->create();
        $editor = User::factory()->create();
        $editor->givePermissionTo(Permission::ManageDocuments->value);

        $this->actingAs($editor)
            ->get(route('dashboard'))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('access.event', false)
                    ->where('access.documents', false),
            );
    }

    #[Test]
    public function it_reports_no_registration_for_an_account_without_one(): void
    {
        $this->openEvent();

        $this->actingAs(User::factory()->participant()->create())
            ->get(route('dashboard'))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('access.registration', false),
            );
    }

    #[Test]
    public function it_reports_a_registration_the_account_holds(): void
    {
        $event = $this->openEvent();
        $runner = User::factory()->participant()->create();
        Participant::factory()->create([
            'event_id' => $event->id,
            'user_id' => $runner->id,
        ]);

        $this->actingAs($runner)
            ->get(route('dashboard'))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('access.registration', true),
            );
    }

    #[Test]
    public function it_opens_both_sets_to_a_manager_who_also_holds_a_registration(): void
    {
        $event = $this->openEvent();
        $manager = User::factory()->manager()->create();
        Participant::factory()->create([
            'event_id' => $event->id,
            'user_id' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->get(route('dashboard'))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('access', [
                        'event' => true,
                        'documents' => true,
                        'registration' => true,
                    ])
                    ->where('auth.permissions.'.Permission::ManageEvent->value, true),
            );
    }

    #[Test]
    public function it_closes_the_event_areas_when_no_event_exists(): void
    {
        $this->assertDatabaseCount('events', 0);

        $this->actingAs(User::factory()->participant()->create())
            ->get(route('dashboard'))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('access.event', false)
                    ->where('access.documents', false)
                    ->where('access.registration', false),
            );
    }
}
