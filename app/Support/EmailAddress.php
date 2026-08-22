<?php

namespace App\Support;

use Illuminate\Support\Str;

final class EmailAddress
{
    public static function normalise(?string $typed): string
    {
        return Str::lower(trim($typed ?? ''));
    }
}
