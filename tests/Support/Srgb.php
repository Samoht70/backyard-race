<?php

namespace Tests\Support;

class Srgb
{
    /**
     * @throws UnparsableColorException
     */
    public static function hex(string $oklch): string
    {
        $channels = array_map(
            fn (float $channel): int => (int) round(self::encode($channel) * 255),
            self::linear($oklch),
        );

        return vsprintf('#%02x%02x%02x', $channels);
    }

    /**
     * @return array{float, float, float}
     *
     * @throws UnparsableColorException
     */
    public static function linear(string $oklch): array
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

    private static function encode(float $channel): float
    {
        return $channel <= 0.0031308
            ? 12.92 * $channel
            : 1.055 * $channel ** (1 / 2.4) - 0.055;
    }

    /**
     * @return array{float, float, float}
     *
     * @throws UnparsableColorException
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
