<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Participant;
use App\Models\User;

class ParticipantPolicy
{
    public function view(User $user, Participant $participant): bool
    {
        return $participant->user_id === $user->id;
    }

    public function update(User $user, Participant $participant): bool
    {
        return $participant->user_id === $user->id
            && $participant->lifecycle()->isEditableByRunner();
    }

    public function manage(User $user, Participant $participant): bool
    {
        return $user->can(Permission::ManageParticipants->value);
    }
}
