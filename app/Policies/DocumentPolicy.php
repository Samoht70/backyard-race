<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Document;
use App\Models\Event;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user, Event $event): bool
    {
        return $event->lifecycle()->isVisibleToParticipants()
            || $user->can(Permission::ManageEvent->value);
    }

    public function create(User $user, Event $event): bool
    {
        return $user->can(Permission::ManageDocuments->value)
            && $event->lifecycle()->isEditable();
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->create($user, $document->event);
    }
}
