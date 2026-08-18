<?php

namespace Tests\Support;

class CssTokens
{
    /**
     * @param  array<string, string>  $light
     * @param  array<string, string>  $dark
     */
    private function __construct(
        private readonly array $light,
        private readonly array $dark,
    ) {}

    public static function fromStylesheet(string $path): self
    {
        $source = (string) file_get_contents($path);

        return new self(
            self::block($source, ':root'),
            self::block($source, '.dark'),
        );
    }

    /**
     * @return array<string, string>
     */
    public function theme(string $theme): array
    {
        return $theme === 'dark' ? $this->dark : $this->light;
    }

    /**
     * @return array<string, string>
     */
    private static function block(string $source, string $selector): array
    {
        $pattern = '/'.preg_quote($selector, '/').'\s*\{(.*?)\n\}/s';

        if (preg_match($pattern, $source, $matched) !== 1) {
            throw new StylesheetBlockNotFoundException($selector);
        }

        preg_match_all(
            '/--([a-z0-9-]+):\s*(oklch\([^)]*\))\s*;/i',
            $matched[1],
            $declarations,
            PREG_SET_ORDER,
        );

        $tokens = [];

        foreach ($declarations as $declaration) {
            $tokens[$declaration[1]] = $declaration[2];
        }

        return $tokens;
    }
}
