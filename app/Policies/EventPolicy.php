<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Event;
use App\Models\User;

class EventPolicy
{
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

    public function updateBriefing(User $user, Event $event): bool
    {
        return $user->can(Permission::ManageDocuments->value)
            && $event->lifecycle()->isEditable();
    }

    public function changeSchedule(User $user, Event $event): bool
    {
        return $user->can(Permission::ManageEvent->value);
    }

    public function correctLaps(User $user, Event $event): bool
    {
        return $user->can(Permission::ManageLaps->value)
            && $event->lifecycle()->isRacing();
    }

    public function advance(User $user, Event $event): bool
    {
        $permission = $event->lifecycle()->advancePermission();

        return $permission !== null && $user->can($permission->value);
    }

    public function revert(User $user, Event $event): bool
    {
        return $event->lifecycle()->previousStatus() !== null
            && $user->can(Permission::ManageEvent->value);
    }
}
