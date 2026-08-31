<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Always a 409, and the message reaches the client with debug off, so it is
 * translated. A manager refused through the screen gets a validation error
 * from the form request instead; this fires when nothing else caught it.
 */
final class RoundDurationRefusedException extends ConflictHttpException
{
    public static function roundStarted(): self
    {
        return new self(__('race.refusal.round_started'));
    }

    public static function noSchedule(): self
    {
        return new self(__('race.refusal.no_schedule'));
    }
}
