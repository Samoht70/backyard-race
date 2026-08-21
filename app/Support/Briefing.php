<?php

namespace App\Support;

use Illuminate\Support\Str;

final class Briefing
{
    private const RAW_ELEMENTS = 'script|style|iframe|object|embed|svg|math|template|noscript|form';

    private const REMOVALS = [
        '/<!--.*?-->/s',
        '/<[!?][^>]*>?/s',
        '#<\s*('.self::RAW_ELEMENTS.')\b[^>]*>.*?<\s*/\s*\1\s*>#is',
        '#<\s*('.self::RAW_ELEMENTS.')\b[^>]*>.*#is',
        '#</?[a-zA-Z][a-zA-Z0-9:-]*(?:\s[^<>]*)?/?>#',
    ];

    private const UNSAFE_SCHEMES = '/(\]\(\s*|<)\s*(?:javascript|data|vbscript)\s*:/i';

    private const RENDER = [
        'html_input' => 'strip',
        'allow_unsafe_links' => false,
    ];

    public static function clean(string $submitted): string
    {
        $withoutHtml = preg_replace(
            self::REMOVALS,
            '',
            str_replace(["\r\n", "\r"], "\n", $submitted),
        ) ?? '';

        return trim(preg_replace(self::UNSAFE_SCHEMES, '$1', $withoutHtml) ?? '');
    }

    public static function toHtml(string $briefing): string
    {
        return Str::markdown($briefing, self::RENDER);
    }

    public static function orDefault(?string $briefing): string
    {
        return $briefing === null || trim($briefing) === ''
            ? __('briefing.default')
            : $briefing;
    }
}
