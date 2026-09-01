<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class LapCorrectionRefusedException extends ConflictHttpException
{
    public static function alreadyValidated(): self
    {
        return new self(__('race.refusal.lap_already_validated'));
    }

    public static function notValidated(): self
    {
        return new self(__('race.refusal.lap_not_validated'));
    }

    public static function beforeTheStart(): self
    {
        return new self(__('race.refusal.before_round_start'));
    }
}
