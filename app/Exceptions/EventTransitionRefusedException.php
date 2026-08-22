<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Always a 409: the request conflicts with the event's current status. The
 * message reaches the client even with debug off, so it is translated rather
 * than internal. This is the net, not the channel — a manager who is refused
 * gets a validation error from the form request instead.
 */
final class EventTransitionRefusedException extends ConflictHttpException
{
    public static function incomplete(): self
    {
        return new self(__('event.refusal.incomplete'));
    }

    public static function illegal(): self
    {
        return new self(__('event.refusal.illegal_transition'));
    }

    public static function terminal(): self
    {
        return new self(__('event.refusal.finished'));
    }

    public static function registrationsExist(): self
    {
        return new self(__('event.refusal.registrations_exist'));
    }
}
