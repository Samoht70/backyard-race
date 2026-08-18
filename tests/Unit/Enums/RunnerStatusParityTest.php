<?php

namespace Tests\Unit\Enums;

use App\Enums\RunnerStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RunnerStatusParityTest extends TestCase
{
    #[Test]
    public function it_declares_every_php_case_in_the_typescript_status_list(): void
    {
        $declared = $this->typescriptStatuses();
        $cases = array_column(RunnerStatus::cases(), 'value');

        sort($declared);
        sort($cases);

        $this->assertSame(
            $cases,
            $declared,
            'resources/js/types/race.ts must declare exactly the RunnerStatus cases.',
        );
    }

    /**
     * @return list<string>
     */
    private function typescriptStatuses(): array
    {
        $path = dirname(__DIR__, 3).'/resources/js/types/race.ts';

        $this->assertFileExists($path);

        $source = (string) file_get_contents($path);

        $this->assertSame(
            1,
            preg_match('/RUNNER_STATUSES\s*=\s*\[(.*?)\]/s', $source, $bracketed),
            'Could not locate the RUNNER_STATUSES array.',
        );

        preg_match_all("/'([a-z_]+)'/", $bracketed[1], $found);

        return $found[1];
    }
}
