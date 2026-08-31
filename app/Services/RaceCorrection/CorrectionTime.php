<?php

namespace App\Services\RaceCorrection;

use App\Models\Round;
use Carbon\CarbonImmutable;

final class CorrectionTime
{
    public static function on(Round $round, string $wallClock): CarbonImmutable
    {
        $instant = $round->starts_at->setTimeFromTimeString($wallClock);

        return $instant->lessThan($round->starts_at) && self::spansMidnight($round)
            ? $instant->addDay()
            : $instant;
    }

    private static function spansMidnight(Round $round): bool
    {
        return ! $round->starts_at->isSameDay($round->deadline_at);
    }
}
