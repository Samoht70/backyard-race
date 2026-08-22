<?php

namespace App\Support;

use App\Models\Event;

final class RegistrationDeletion
{
    public static function refusal(Event $event): ?string
    {
        if (! $event->lifecycle()->isRacing()) {
            return null;
        }

        return __('registration.refusal.racing', ['status' => $event->status->label()]);
    }
}
