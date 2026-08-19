<?php

namespace App\Policies;

use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\Participant;
use App\Models\User;

class ParticipantPolicy
{
    public function create(User $user, Event $event): bool
    {
        return $event->lifecycle()->allowsRegistration()
            && ! $user->participant()->exists();
    }

    public function view(User $user, Participant $participant): bool
    {
        return $participant->user_id === $user->id;
    }

    public function update(User $user, Participant $participant): bool
    {
        return $participant->user_id === $user->id
            && $participant->status === RegistrationStatus::Pending;
    }
}
