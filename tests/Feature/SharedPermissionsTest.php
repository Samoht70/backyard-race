<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SharedPermissionsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shares_every_ability_as_false_for_a_guest(): void
    {
        $response = $this->get(route('home'));

        $response->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('auth.permissions', $this->everyAbility(false)),
        );
    }

    #[Test]
    public function it_shares_every_ability_as_true_for_a_manager(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $response = $this->actingAs(User::factory()->manager()->create())
            ->get(route('dashboard'));

        $response->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('auth.permissions', $this->everyAbility(true)),
        );
    }

    #[Test]
    public function it_shares_every_ability_as_false_for_a_participant(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $response = $this->actingAs(User::factory()->participant()->create())
            ->get(route('dashboard'));

        $response->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('auth.permissions', $this->everyAbility(false)),
        );
    }

    #[Test]
    public function it_keeps_the_role_and_permission_relations_out_of_the_shared_user(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $response = $this->actingAs(User::factory()->manager()->create())
            ->get(route('dashboard'));

        $response->assertInertia(
            fn (AssertableInertia $page) => $page
                ->missing('auth.user.roles')
                ->missing('auth.user.permissions'),
        );
    }

    /**
     * @return array<string, bool>
     */
    private function everyAbility(bool $isGranted): array
    {
        return array_fill_keys(array_column(Permission::cases(), 'value'), $isGranted);
    }
}
