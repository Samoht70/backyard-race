<?php

namespace Tests\Support;

class Contrast
{
    public static function ratio(string $first, string $second): float
    {
        $brighter = max(self::luminance($first), self::luminance($second));
        $darker = min(self::luminance($first), self::luminance($second));

        return ($brighter + 0.05) / ($darker + 0.05);
    }

    private static function luminance(string $oklch): float
    {
        [$red, $green, $blue] = self::toLinearRgb($oklch);

        return 0.2126 * $red + 0.7152 * $green + 0.0722 * $blue;
    }

    /**
     * Oklab to linear sRGB, after Björn Ottosson's reference conversion.
     *
     * @return array{float, float, float}
     */
    private static function toLinearRgb(string $oklch): array
    {
        [$lightness, $chroma, $hue] = self::parse($oklch);

        $aAxis = $chroma * cos(deg2rad($hue));
        $bAxis = $chroma * sin(deg2rad($hue));

        $long = ($lightness + 0.3963377774 * $aAxis + 0.2158037573 * $bAxis) ** 3;
        $medium = ($lightness - 0.1055613458 * $aAxis - 0.0638541728 * $bAxis) ** 3;
        $short = ($lightness - 0.0894841775 * $aAxis - 1.2914855480 * $bAxis) ** 3;

        return [
            self::clamp(4.0767416621 * $long - 3.3077115913 * $medium + 0.2309699292 * $short),
            self::clamp(-1.2684380046 * $long + 2.6097574011 * $medium - 0.3413193965 * $short),
            self::clamp(-0.0041960863 * $long - 0.7034186147 * $medium + 1.7076147010 * $short),
        ];
    }

    /**
     * @return array{float, float, float}
     */
    private static function parse(string $oklch): array
    {
        if (preg_match('/oklch\(\s*([\d.]+)\s+([\d.]+)\s+([\d.]+)\s*\)/i', $oklch, $parts) !== 1) {
            throw new UnparsableColorException($oklch);
        }

        return [(float) $parts[1], (float) $parts[2], (float) $parts[3]];
    }

    private static function clamp(float $channel): float
    {
        return max(0.0, min(1.0, $channel));
    }
}
