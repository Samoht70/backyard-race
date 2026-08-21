<?php

namespace App\Support;

use Illuminate\Support\Str;

final class AccessCode
{
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private const GROUPS = 3;

    private const GROUP_LENGTH = 4;

    public static function generate(): string
    {
        $code = '';

        for ($position = 0; $position < self::GROUPS * self::GROUP_LENGTH; $position++) {
            if ($position > 0 && $position % self::GROUP_LENGTH === 0) {
                $code .= '-';
            }

            $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        return $code;
    }

    public static function normalise(string $typed): string
    {
        $characters = preg_replace('/[^'.self::ALPHABET.']/', '', Str::upper($typed)) ?? '';

        return implode('-', str_split($characters, self::GROUP_LENGTH) ?: []);
    }
}
