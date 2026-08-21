<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class RegistrationTransitionRefusedException extends ConflictHttpException
{
    public static function illegal(): self
    {
        return new self(__('registration.refusal.illegal_transition'));
    }

    public static function full(): self
    {
        return new self(__('registration.refusal.full'));
    }

    public static function stale(): self
    {
        return new self(__('registration.refusal.stale'));
    }
}
