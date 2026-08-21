<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DesignSystemTest extends TestCase
{
    #[Test]
    public function it_renders_the_showcase_without_authentication(): void
    {
        $response = $this->get(route('design-system'));

        $response->assertOk();
        $response->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('DesignSystem')
                ->has('statuses', 4)
                ->where('statuses.0.label', 'En course'),
        );
    }
}
