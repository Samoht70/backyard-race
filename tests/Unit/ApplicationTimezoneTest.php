<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApplicationTimezoneTest extends TestCase
{
    #[Test]
    public function it_runs_on_paris_time(): void
    {
        $this->assertSame('Europe/Paris', config('app.timezone'));
        $this->assertSame('Europe/Paris', date_default_timezone_get());
    }

    #[Test]
    public function it_reads_the_timezone_from_the_environment(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2).'/config/app.php');

        $this->assertStringContainsString(
            "'timezone' => env('APP_TIMEZONE', 'UTC')",
            $source,
            'A hard-coded timezone silently ignores APP_TIMEZONE and shifts every lap by two hours.',
        );
    }
}
