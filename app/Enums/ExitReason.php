<?php

namespace App\Enums;

enum ExitReason: string
{
    case Withdrawal = 'withdrawal';
    case Timeout = 'timeout';

    public function runnerStatus(): RunnerStatus
    {
        return match ($this) {
            self::Withdrawal => RunnerStatus::Withdrawn,
            self::Timeout => RunnerStatus::Eliminated,
        };
    }
}
