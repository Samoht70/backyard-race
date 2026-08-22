<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\User;
use Database\Factories\UserFactory;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ManagerAccountCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_manager_whose_shown_code_logs_in(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $status = Artisan::call('race:manager-account', [
            'email' => 'orga@backyard.test',
            'first-name' => 'Claire',
            'last-name' => 'Fontaine',
        ]);

        $account = User::query()->where('email', 'orga@backyard.test')->sole();

        $this->assertSame(Command::SUCCESS, $status);
        $this->assertTrue($account->hasRole(Role::Manager));

        $this->post(route('login.store'), [
            'email' => 'orga@backyard.test',
            'password' => $this->shownCode(),
        ]);

        $this->assertAuthenticatedAs($account);
    }

    #[Test]
    public function it_stores_the_address_in_lower_case_without_surrounding_spaces(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $status = Artisan::call('race:manager-account', [
            'email' => '  Orga@Backyard.Test ',
            'first-name' => 'Claire',
            'last-name' => 'Fontaine',
        ]);

        $this->assertSame(Command::SUCCESS, $status);
        $this->assertDatabaseHas('users', ['email' => 'orga@backyard.test']);

        $this->post(route('login.store'), [
            'email' => '  Orga@Backyard.Test ',
            'password' => $this->shownCode(),
        ]);

        $this->assertAuthenticated();
    }

    #[Test]
    public function it_refuses_a_second_account_on_a_taken_address(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        User::factory()->manager()->create(['email' => 'orga@backyard.test']);

        $status = Artisan::call('race:manager-account', [
            'email' => 'orga@backyard.test',
            'first-name' => 'Claire',
            'last-name' => 'Fontaine',
        ]);

        $this->assertSame(Command::FAILURE, $status);
        $this->assertStringContainsString('--regenerate', Artisan::output());
        $this->assertDatabaseCount('users', 1);
    }

    #[Test]
    public function it_refuses_before_writing_when_the_roles_are_not_seeded(): void
    {
        $status = Artisan::call('race:manager-account', [
            'email' => 'orga@backyard.test',
            'first-name' => 'Claire',
            'last-name' => 'Fontaine',
        ]);

        $this->assertSame(Command::FAILURE, $status);
        $this->assertStringContainsString('RolesAndPermissionsSeeder', Artisan::output());
        $this->assertDatabaseCount('users', 0);
    }

    #[Test]
    public function it_refuses_an_address_that_is_not_one(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $status = Artisan::call('race:manager-account', [
            'email' => 'orga-at-backyard',
            'first-name' => 'Claire',
            'last-name' => 'Fontaine',
        ]);

        $this->assertSame(Command::FAILURE, $status);
        $this->assertDatabaseCount('users', 0);
    }

    #[Test]
    public function it_refuses_to_create_an_account_without_a_name(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $status = Artisan::call('race:manager-account', ['email' => 'orga@backyard.test']);

        $this->assertSame(Command::FAILURE, $status);
        $this->assertDatabaseCount('users', 0);
    }

    #[Test]
    public function it_replaces_the_code_and_leaves_the_role_alone_on_regeneration(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $account = User::factory()->manager()->create(['email' => 'orga@backyard.test']);

        $status = Artisan::call('race:manager-account', ['email' => 'orga@backyard.test', '--regenerate' => true]);
        $code = $this->shownCode();

        $this->assertSame(Command::SUCCESS, $status);

        $this->post(route('login.store'), [
            'email' => 'orga@backyard.test',
            'password' => UserFactory::ACCESS_CODE,
        ]);

        $this->assertGuest();

        $this->post(route('login.store'), ['email' => 'orga@backyard.test', 'password' => $code]);

        $this->assertAuthenticatedAs($account);
        $this->assertSame([Role::Manager->value], $account->fresh()?->getRoleNames()->all());
    }

    #[Test]
    public function it_leaves_a_runner_a_runner_when_it_reissues_their_code(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $runner = User::factory()->participant()->create(['email' => 'coureur@backyard.test']);

        $status = Artisan::call('race:manager-account', ['email' => 'coureur@backyard.test', '--regenerate' => true]);

        $this->assertSame(Command::SUCCESS, $status);
        $this->assertSame([Role::Participant->value], $runner->fresh()?->getRoleNames()->all());
    }

    private function shownCode(): string
    {
        preg_match('/[A-Z2-9]{4}-[A-Z2-9]{4}-[A-Z2-9]{4}/', Artisan::output(), $matches);

        return $matches[0] ?? '';
    }
}
