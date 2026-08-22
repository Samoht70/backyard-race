<?php

namespace App\Providers;

use App\Enums\Permission;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    protected function gate(): void
    {
        Gate::define('viewHorizon', fn ($user = null) => $user?->can(Permission::ManageEvent->value) ?? false);
    }
}
