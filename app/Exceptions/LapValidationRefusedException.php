<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class LapValidationRefusedException extends ConflictHttpException
{
    public static function deadlinePassed(): self
    {
        return new self(__('race.refusal.deadline_passed'));
    }

    public static function runnerOut(): self
    {
        return new self(__('race.refusal.runner_out'));
    }
}
