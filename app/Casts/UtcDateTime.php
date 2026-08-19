<?php

namespace App\Casts;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Stores the instant in UTC and reads it back in the application timezone.
 *
 * The default datetime cast writes Paris wall-clock into a column that carries
 * no offset. On the October night the local hour 02:00 is lived twice: two
 * rounds store the same "02:00:00" and reading the earlier one back moves it
 * one hour later — the deadline that eliminates runners.
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
