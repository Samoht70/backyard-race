<?php

namespace App\Support;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role as RoleModel;

final class SparedAccount
{
    public static function spares(User $account): bool
    {
        return $account->hasRole(Role::Manager) || $account->email === OrganiserAddress::configured();
    }

    /**
     * @return Builder<User>
     */
    public static function runners(): Builder
    {
        $accounts = User::query()->whereDoesntHave('roles', self::managerRole(...));
        $organiser = OrganiserAddress::configured();

        return $organiser === null ? $accounts : $accounts->where('email', '!=', $organiser);
    }

    /**
     * @param  Builder<RoleModel>  $roles
     */
    private static function managerRole(Builder $roles): void
    {
        $roles->where('name', Role::Manager->value);
    }
}
