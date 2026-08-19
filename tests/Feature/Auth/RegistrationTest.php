<?php

namespace Tests\Feature\Auth;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register()
    {
        $response = $this->post(route('register.store'), [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    #[Test]
    public function it_gives_a_new_registration_the_participant_role(): void
    {
        $user = $this->register();

        $this->assertTrue($user->hasRole(Role::Participant->value));
    }

    #[Test]
    public function it_gives_a_new_registration_no_administration_permission(): void
    {
        $user = $this->register();

        foreach (Permission::cases() as $permission) {
            $this->assertFalse($user->can($permission->value));
        }
    }

    private function register(): User
    {
        $this->post(route('register.store'), [
            'first_name' => 'Nouvelle',
            'last_name' => 'Recrue',
            'email' => 'recrue@backyard.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        return User::query()->where('email', 'recrue@backyard.test')->sole();
    }
}
