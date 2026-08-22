<?php

namespace Tests\Support;

class Contrast
{
    /**
     * @throws UnparsableColorException
     */
    public static function ratio(string $first, string $second): float
    {
        $brighter = max(self::luminance($first), self::luminance($second));
        $darker = min(self::luminance($first), self::luminance($second));

        return ($brighter + 0.05) / ($darker + 0.05);
    }

    /**
     * @throws UnparsableColorException
     */
    private static function luminance(string $oklch): float
    {
        [$red, $green, $blue] = Srgb::linear($oklch);

        return 0.2126 * $red + 0.7152 * $green + 0.0722 * $blue;
    }
}
