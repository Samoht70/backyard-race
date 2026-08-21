<?php

namespace Tests\Unit\Enums;

use App\Enums\RegistrationTransition;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RegistrationTransitionParityTest extends TestCase
{
    #[Test]
    public function it_declares_every_php_case_in_the_typescript_transition_list(): void
    {
        $declared = $this->typescriptTransitions();
        $cases = array_column(RegistrationTransition::cases(), 'value');

        sort($declared);
        sort($cases);

        $this->assertSame(
            $cases,
            $declared,
            'resources/js/types/registration.ts must declare exactly the RegistrationTransition cases.',
        );
    }

    /**
     * @return list<string>
     */
    private function typescriptTransitions(): array
    {
        $path = dirname(__DIR__, 3).'/resources/js/types/registration.ts';

        $this->assertFileExists($path);

        $source = (string) file_get_contents($path);

        $this->assertSame(
            1,
            preg_match('/REGISTRATION_TRANSITIONS\s*=\s*\[(.*?)\]/s', $source, $bracketed),
            'Could not locate the REGISTRATION_TRANSITIONS array.',
        );

        preg_match_all("/'([a-z_]+)'/", $bracketed[1], $found);

        return $found[1];
    }
}
