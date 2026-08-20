<?php

namespace Tests\Unit\Support;

use App\Support\BibNumber;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class BibNumberTest extends TestCase
{
    #[Test]
    public function it_pads_a_number_to_three_digits(): void
    {
        $this->assertSame('001', BibNumber::label(1));
        $this->assertSame('040', BibNumber::label(40));
    }

    #[Test]
    public function it_leaves_a_number_beyond_a_hundred_unchanged(): void
    {
        $this->assertSame('128', BibNumber::label(128));
        $this->assertSame('1042', BibNumber::label(1042));
    }

    #[Test]
    public function it_has_no_label_without_a_number(): void
    {
        $this->assertNull(BibNumber::label(null));
    }
}
