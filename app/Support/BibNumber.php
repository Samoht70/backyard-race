<?php

namespace App\Support;

final class BibNumber
{
    private const DIGITS = 3;

    public static function label(?int $number): ?string
    {
        return $number === null
            ? null
            : str_pad((string) $number, self::DIGITS, '0', STR_PAD_LEFT);
    }
}
