<?php

namespace App\Services\RaceResult;

use App\Models\Round;
use Carbon\CarbonImmutable;

final class LapPerformance
{
    private const METERS_PER_KILOMETER = 1000;

    private const SECONDS_PER_HOUR = 3600;

    private function __construct(
        public CarbonImmutable $validatedAt,
        public int $durationSeconds,
        public ?int $distanceMeters,
        public ?float $speedKmh,
    ) {}

    public static function of(Round $round, CarbonImmutable $validatedAt, ?int $distanceMeters): self
    {
        $durationSeconds = $validatedAt->getTimestamp() - $round->starts_at->getTimestamp();

        return new self(
            $validatedAt,
            $durationSeconds,
            $distanceMeters,
            self::speedKmh($durationSeconds, $distanceMeters),
        );
    }

    private static function speedKmh(int $durationSeconds, ?int $distanceMeters): ?float
    {
        if ($distanceMeters === null || $distanceMeters < 1 || $durationSeconds < 1) {
            return null;
        }

        $kilometers = $distanceMeters / self::METERS_PER_KILOMETER;
        $hours = $durationSeconds / self::SECONDS_PER_HOUR;

        return round($kilometers / $hours, 2);
    }
}
