<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class RunnerExitRefusedException extends ConflictHttpException
{
    public static function alreadyOut(): self
    {
        return new self(__('race.refusal.runner_already_out'));
    }
}
