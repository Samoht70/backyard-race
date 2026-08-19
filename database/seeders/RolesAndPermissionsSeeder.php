<?php

namespace Database\Seeders;

use App\Enums\Permission;
use App\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Contracts\Permission as PermissionContract;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * The leading flush is load-bearing: Permission::findOrCreate resolves
     * against the registrar's cached snapshot rather than the table, so a stale
     * snapshot makes a second run insert duplicates and hit the name/guard
     * unique index.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $abilities = array_map(
            fn (Permission $permission): PermissionContract => PermissionModel::findOrCreate($permission),
            Permission::cases(),
        );

        RoleModel::findOrCreate(Role::Participant)->syncPermissions([]);
        RoleModel::findOrCreate(Role::Manager)->syncPermissions($abilities);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
