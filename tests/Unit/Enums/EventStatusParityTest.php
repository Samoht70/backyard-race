<?php

namespace Tests\Unit\Enums;

use App\Enums\EventStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class EventStatusParityTest extends TestCase
{
    /**
     * Order is asserted, not just membership: the manager's step list is built
     * by indexOf against this array, so a reordering there would silently
     * redraw the lifecycle the state classes own.
     */
    #[Test]
    public function it_declares_every_php_case_in_the_typescript_event_status_list(): void
    {
        $this->assertSame(
            array_column(EventStatus::cases(), 'value'),
            $this->typescriptStatuses(),
            'resources/js/types/event.ts must declare the EventStatus cases, in the same order.',
        );
    }

    /**
     * @return list<string>
     */
    private function typescriptStatuses(): array
    {
        $path = dirname(__DIR__, 3).'/resources/js/types/event.ts';

        $this->assertFileExists($path);

        $source = (string) file_get_contents($path);

        $this->assertSame(
            1,
            preg_match('/EVENT_STATUSES\s*=\s*\[(.*?)\]/s', $source, $bracketed),
            'Could not locate the EVENT_STATUSES array.',
        );

        preg_match_all("/'([a-z_]+)'/", $bracketed[1], $found);

        return $found[1];
    }
}
