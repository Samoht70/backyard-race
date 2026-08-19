<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;
use Tests\TestCase;

class RolesAndPermissionsSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_the_nine_expected_permissions(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertEqualsCanonicalizing(
            array_column(Permission::cases(), 'value'),
            PermissionModel::query()->pluck('name')->all(),
        );
    }

    #[Test]
    public function it_attaches_every_permission_to_the_manager_role(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertEqualsCanonicalizing(
            array_column(Permission::cases(), 'value'),
            RoleModel::findByName(Role::Manager->value)->permissions->pluck('name')->all(),
        );
    }

    #[Test]
    public function it_attaches_no_permission_to_the_participant_role(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertCount(0, RoleModel::findByName(Role::Participant->value)->permissions);
    }

    #[Test]
    public function it_can_run_twice_without_duplicating_anything(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertSame(count(Permission::cases()), PermissionModel::query()->count());
        $this->assertSame(count(Role::cases()), RoleModel::query()->count());
        $this->assertCount(
            count(Permission::cases()),
            RoleModel::findByName(Role::Manager->value)->permissions,
        );
    }

    #[Test]
    public function it_leaves_the_permission_cache_cold(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertFalse(Cache::has(config('permission.cache.key')));
    }
}
