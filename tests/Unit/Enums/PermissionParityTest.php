<?php

namespace Tests\Unit\Enums;

use App\Enums\Permission;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PermissionParityTest extends TestCase
{
    #[Test]
    public function it_declares_every_php_case_in_the_typescript_permission_list(): void
    {
        $declared = $this->typescriptPermissions();
        $cases = array_column(Permission::cases(), 'value');

        sort($declared);
        sort($cases);

        $this->assertSame(
            $cases,
            $declared,
            'resources/js/types/permission.ts must declare exactly the Permission cases.',
        );
    }

    /**
     * @return list<string>
     */
    private function typescriptPermissions(): array
    {
        $path = dirname(__DIR__, 3).'/resources/js/types/permission.ts';

        $this->assertFileExists($path);

        $source = (string) file_get_contents($path);

        $this->assertSame(
            1,
            preg_match('/PERMISSIONS\s*=\s*\[(.*?)\]/s', $source, $bracketed),
            'Could not locate the PERMISSIONS array.',
        );

        preg_match_all("/'([a-z-]+)'/", $bracketed[1], $found);

        return $found[1];
    }
}
