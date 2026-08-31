<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Lap;
use App\Models\User;

class LapPolicy
{
    public function validate(User $user, Lap $lap): bool
    {
        return $user->can(Permission::ValidateLaps->value)
            && $lap->round->event->lifecycle()->isRacing();
    }

    public function correct(User $user, Lap $lap): bool
    {
        return $user->can('correctLaps', $lap->round->event);
    }
}
