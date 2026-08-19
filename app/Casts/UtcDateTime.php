<?php

namespace App\Casts;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * A DATETIME column carries no offset: written as local wall-clock, the hour
 * lived twice on the autumn clock change reads back one hour late, moving the
 * deadline that eliminates runners.
 *
 * @implements CastsAttributes<CarbonImmutable, CarbonImmutable|string>
 */
final class UtcDateTime implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $record, string $attribute, mixed $stored, array $attributes): ?CarbonImmutable
    {
        if (! is_string($stored)) {
            return null;
        }

        return CarbonImmutable::parse($stored, 'UTC')
            ->setTimezone(config()->string('app.timezone'));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $record, string $attribute, mixed $instant, array $attributes): ?string
    {
        if (! $instant instanceof DateTimeInterface && ! is_string($instant)) {
            return null;
        }

        return CarbonImmutable::parse($instant)->utc()->format('Y-m-d H:i:s');
    }
}
