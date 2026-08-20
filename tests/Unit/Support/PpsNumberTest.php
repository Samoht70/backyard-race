<?php

namespace Tests\Unit\Support;

use App\Support\PpsNumber;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PpsNumberTest extends TestCase
{
    #[Test]
    public function it_upper_cases_a_number_typed_in_lower_case(): void
    {
        $this->assertSame('PPS12345678', PpsNumber::normalise('pps12345678'));
    }

    #[Test]
    public function it_drops_the_spaces_and_dashes_a_runner_types(): void
    {
        $this->assertSame('PPS12345678', PpsNumber::normalise('pps 1234 5678'));
        $this->assertSame('PPS12345678', PpsNumber::normalise('PPS-1234-5678'));
    }

    #[Test]
    public function it_drops_the_non_breaking_space_of_a_paste(): void
    {
        $this->assertSame('PPS12345678', PpsNumber::normalise("PPS12345678\u{00A0}"));
    }

    #[Test]
    public function it_has_no_number_without_a_declaration(): void
    {
        $this->assertNull(PpsNumber::normalise(null));
        $this->assertNull(PpsNumber::normalise('   '));
    }

    #[Test]
    public function it_matches_three_letters_followed_by_eight_digits(): void
    {
        $this->assertSame(1, preg_match(PpsNumber::PATTERN, 'PPS12345678'));
        $this->assertSame(0, preg_match(PpsNumber::PATTERN, '12345678'));
        $this->assertSame(0, preg_match(PpsNumber::PATTERN, 'PPS1234567'));
        $this->assertSame(0, preg_match(PpsNumber::PATTERN, 'pps12345678'));
    }
}
