<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

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
