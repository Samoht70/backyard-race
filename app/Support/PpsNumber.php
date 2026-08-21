<?php

namespace App\Support;

use Illuminate\Support\Str;

final class PpsNumber
{
    public const PATTERN = '/^[A-Z]{3}\d{8}$/';

    private const SEPARATORS = '/[\s\x{00A0}-]+/u';

    public static function normalise(?string $typed): ?string
    {
        $number = preg_replace(self::SEPARATORS, '', Str::upper($typed ?? '')) ?? '';

        return $number === '' ? null : $number;
    }
}
