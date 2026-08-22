<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Document;
use App\Models\Event;
use App\Models\Participant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PurgeRegistrationsCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_every_registration_and_runner_account(): void
    {
        $event = $this->registrationEvent();
        Participant::factory()->count(4)->for($event)->create();

        $status = Artisan::call('race:purge-registrations', ['--force' => true]);

        $this->assertSame(Command::SUCCESS, $status);
        $this->assertDatabaseCount('participants', 0);
        $this->assertDatabaseCount('users', 0);
    }

    #[Test]
    public function it_leaves_the_organiser_account_and_its_role_alone(): void
    {
        $event = $this->registrationEvent();
        $organiser = User::factory()->manager()->create();
        Participant::factory()->count(4)->for($event)->create();

        Artisan::call('race:purge-registrations', ['--force' => true]);

        $this->assertDatabaseCount('users', 1);
        $this->assertTrue($organiser->fresh()?->hasRole(Role::Manager));
    }

    #[Test]
    public function it_leaves_the_event_its_briefing_and_its_documents_alone(): void
    {
        $event = $this->registrationEvent();
        Document::factory()->for($event)->create(['title' => 'Règlement']);
        Participant::factory()->for($event)->create();

        Artisan::call('race:purge-registrations', ['--force' => true]);

        $this->assertSame($event->briefing, $event->fresh()?->briefing);
        $this->assertDatabaseCount('events', 1);
        $this->assertDatabaseHas('documents', ['title' => 'Règlement']);
    }

    #[Test]
    public function it_takes_the_registration_of_a_manager_and_leaves_the_account(): void
    {
        $event = $this->registrationEvent();
        $organiser = User::factory()->manager()->create();
        Participant::factory()->for($event)->for($organiser)->create();

        Artisan::call('race:purge-registrations', ['--force' => true]);

        $this->assertDatabaseCount('participants', 0);
        $this->assertSame([Role::Manager->value], $organiser->fresh()?->getRoleNames()->all());
    }

    #[Test]
    public function it_spares_the_configured_address_even_when_it_carries_no_role(): void
    {
        $event = $this->registrationEvent();
        config()->set('race.organiser_email', 'ORGA@Backyard.Test ');
        $organiser = User::factory()->create(['email' => 'orga@backyard.test']);
        Participant::factory()->for($event)->for($organiser)->create();
        Participant::factory()->count(3)->for($event)->create();

        Artisan::call('race:purge-registrations', ['--force' => true]);

        $this->assertDatabaseCount('participants', 0);
        $this->assertSame([$organiser->getKey()], User::query()->pluck('id')->all());
    }

    #[Test]
    public function it_spares_by_role_alone_when_the_configured_address_is_not_one(): void
    {
        $event = $this->registrationEvent();
        config()->set('race.organiser_email', 'not-an-address');
        User::factory()->manager()->create();
        Participant::factory()->for($event)->create();

        Artisan::call('race:purge-registrations', ['--force' => true]);

        $this->assertDatabaseCount('users', 1);
        $this->assertStringContainsString('RACE_ORGANISER_EMAIL', Artisan::output());
    }

    #[Test]
    public function it_detaches_the_roles_of_the_accounts_it_deletes(): void
    {
        $event = $this->registrationEvent();
        $runner = User::factory()->participant()->create();
        Participant::factory()->for($event)->for($runner)->create();

        Artisan::call('race:purge-registrations', ['--force' => true]);

        $this->assertDatabaseCount('model_has_roles', 0);
    }

    #[Test]
    public function it_deletes_the_sessions_of_the_purged_accounts(): void
    {
        $event = $this->registrationEvent();
        $organiser = User::factory()->manager()->create();
        $runner = Participant::factory()->for($event)->create()->user;

        $this->openSession('runner-session', $runner);
        $this->openSession('organiser-session', $organiser);

        Artisan::call('race:purge-registrations', ['--force' => true]);

        $this->assertDatabaseMissing('sessions', ['id' => 'runner-session']);
        $this->assertDatabaseHas('sessions', ['id' => 'organiser-session']);
    }

    #[Test]
    public function it_deletes_an_account_that_carries_no_registration(): void
    {
        $this->registrationEvent();
        User::factory()->participant()->create();

        Artisan::call('race:purge-registrations', ['--force' => true]);

        $this->assertDatabaseCount('users', 0);
    }

    #[Test]
    public function it_hands_the_next_bib_number_back_to_one(): void
    {
        $event = $this->registrationEvent();
        Participant::factory()->count(4)->confirmed()->for($event)->create();

        Artisan::call('race:purge-registrations', ['--force' => true]);

        $registration = Participant::factory()->confirmed()->for($event)->create();

        $this->assertSame(1, $registration->bib_number);
    }

    #[Test]
    public function it_refuses_while_the_race_is_running(): void
    {
        $event = Event::factory()->running()->create();
        Participant::factory()->for($event)->create();

        $status = Artisan::call('race:purge-registrations', ['--force' => true]);

        $this->assertSame(Command::FAILURE, $status);
        $this->assertStringContainsString('running', Artisan::output());
        $this->assertDatabaseCount('participants', 1);
    }

    #[Test]
    public function it_says_there_is_nothing_to_purge_on_a_clean_base(): void
    {
        $this->registrationEvent();
        User::factory()->manager()->create();

        $status = Artisan::call('race:purge-registrations');

        $this->assertSame(Command::SUCCESS, $status);
        $this->assertStringContainsString('Nothing to purge', Artisan::output());
        $this->assertDatabaseCount('users', 1);
    }

    #[Test]
    public function it_refuses_without_a_terminal_to_confirm_on(): void
    {
        $event = $this->registrationEvent();
        Participant::factory()->for($event)->create();

        $status = Artisan::call('race:purge-registrations', ['--no-interaction' => true]);

        $this->assertSame(Command::FAILURE, $status);
        $this->assertStringContainsString('--force', Artisan::output());
        $this->assertDatabaseCount('participants', 1);
    }

    #[Test]
    public function it_purges_nothing_when_the_confirmation_is_declined(): void
    {
        $event = $this->registrationEvent();
        Participant::factory()->for($event)->create();

        $this->artisan('race:purge-registrations')
            ->expectsConfirmation('Delete them?', 'no')
            ->assertFailed();

        $this->assertDatabaseCount('participants', 1);
    }

    #[Test]
    public function it_warns_when_no_manager_account_would_remain(): void
    {
        $event = $this->registrationEvent();
        Participant::factory()->for($event)->create();

        Artisan::call('race:purge-registrations', ['--force' => true]);

        $this->assertStringContainsString('race:manager-account', Artisan::output());
    }

    private function registrationEvent(): Event
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        return Event::factory()->registration()->create();
    }

    private function openSession(string $id, User $account): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $account->getKey(),
            'payload' => '',
            'last_activity' => 0,
        ]);
    }
}
