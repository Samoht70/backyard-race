<?php

namespace App\Policies;

use App\Enums\EventStatus;
use App\Enums\Permission;
use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    /**
     * Nullable user: the invitation page has to stay answerable for a guest.
     */
    public function view(?User $user, Event $event): bool
    {
        return $event->lifecycle()->isVisibleToParticipants()
            || $user?->can(Permission::ManageEvent->value) === true;
    }

    public function update(User $user, Event $event): bool
    {
        return $user->can(Permission::ManageEvent->value)
            && $event->lifecycle()->isEditable();
    }

    public function advance(User $user, Event $event): bool
    {
        $next = $event->lifecycle()->nextStatus();

        if ($next === null) {
            return false;
        }

        if ($next === EventStatus::Finished) {
            return $user->can(Permission::FinishEvent->value);
        }

        return $user->can(Permission::ManageEvent->value);
    }
}
