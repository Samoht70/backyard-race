<?php

namespace Tests\Unit\DesignSystem;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Support\Contrast;
use Tests\Support\CssTokens;

class PaletteContrastTest extends TestCase
{
    private const NORMAL_TEXT = 4.5;

    private const LARGE_TEXT = 3.0;

    #[Test]
    #[DataProvider('textPairs')]
    public function it_keeps_text_readable_against_its_surface(
        string $theme,
        string $ink,
        string $surface,
        float $minimum,
    ): void {
        $tokens = self::tokens()->theme($theme);

        $this->assertArrayHasKey($ink, $tokens, "Token [{$ink}] is missing from [{$theme}].");
        $this->assertArrayHasKey($surface, $tokens, "Token [{$surface}] is missing from [{$theme}].");

        $ratio = Contrast::ratio($tokens[$ink], $tokens[$surface]);

        $this->assertGreaterThanOrEqual(
            $minimum,
            $ratio,
            sprintf(
                '%s: %s on %s is %.2f:1, below the required %.1f:1.',
                $theme,
                $ink,
                $surface,
                $ratio,
                $minimum,
            ),
        );
    }

    #[Test]
    #[DataProvider('themes')]
    public function it_declares_every_colour_token_in_both_themes(string $theme): void
    {
        $other = $theme === 'dark' ? 'light' : 'dark';

        $missing = array_diff(
            array_keys(self::tokens()->theme($other)),
            array_keys(self::tokens()->theme($theme)),
        );

        $this->assertSame(
            [],
            array_values($missing),
            "Tokens declared in [{$other}] but not in [{$theme}] freeze at the other theme's value.",
        );
    }

    #[Test]
    public function it_matches_the_root_view_background_literals(): void
    {
        $blade = (string) file_get_contents(self::basePath().'/resources/views/app.blade.php');

        preg_match_all('/background-color:\s*(oklch\([^)]*\))/', $blade, $found);

        $this->assertCount(
            2,
            $found[1],
            'The root view should hardcode exactly two background colours, light then dark.',
        );

        $this->assertSame(
            [
                self::tokens()->theme('light')['background'],
                self::tokens()->theme('dark')['background'],
            ],
            $found[1],
            'The anti-flash literals in app.blade.php have drifted from the background tokens.',
        );
    }

    /**
     * @return list<array{string}>
     */
    public static function themes(): array
    {
        return [['light'], ['dark']];
    }

    /**
     * @return array<string, array{string, string, string, float}>
     */
    public static function textPairs(): array
    {
        $pairs = [];

        foreach (['light', 'dark'] as $theme) {
            foreach (self::inkOnSurface() as [$ink, $surface]) {
                $pairs["{$theme}: {$ink} on {$surface}"] = [$theme, $ink, $surface, self::NORMAL_TEXT];
            }

            $pairs["{$theme}: ring on background"] = [$theme, 'ring', 'background', self::LARGE_TEXT];
        }

        return $pairs;
    }

    /**
     * @return list<array{string, string}>
     */
    private static function inkOnSurface(): array
    {
        $pairs = [
            ['foreground', 'background'],
            ['foreground', 'card'],
            ['card-foreground', 'card'],
            ['popover-foreground', 'popover'],
            ['muted-foreground', 'background'],
            ['muted-foreground', 'card'],
            ['muted-foreground', 'muted'],
            ['primary-foreground', 'primary'],
            ['primary', 'background'],
            ['primary', 'card'],
            ['secondary-foreground', 'secondary'],
            ['accent-foreground', 'accent'],
            ['destructive-foreground', 'destructive'],
            ['sidebar-foreground', 'sidebar-background'],
            ['sidebar-accent-foreground', 'sidebar-accent'],
        ];

        foreach (['running', 'eliminated', 'abandoned', 'finished'] as $status) {
            $pairs[] = ["status-{$status}", "status-{$status}-surface"];
            $pairs[] = ["status-{$status}", 'background'];
            $pairs[] = ["status-{$status}", 'card'];
            $pairs[] = ["status-{$status}-foreground", "status-{$status}"];
        }

        return $pairs;
    }

    private static function tokens(): CssTokens
    {
        return CssTokens::fromStylesheet(self::basePath().'/resources/css/app.css');
    }

    private static function basePath(): string
    {
        return dirname(__DIR__, 3);
    }
}
