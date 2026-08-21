<?php

namespace Tests\Unit\Support;

use App\Support\Briefing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class BriefingTest extends TestCase
{
    #[Test]
    public function it_removes_a_script_element_with_its_body(): void
    {
        $this->assertSame('', Briefing::clean('<script>alert(1)</script>'));
    }

    /** Inline HTML keeps its body through the renderer: only the entry cleaning drops `alert(1)`. */
    #[Test]
    public function it_removes_a_script_element_written_inside_a_sentence(): void
    {
        $cleaned = Briefing::clean('Bonjour <script>alert(1)</script> fin');

        $this->assertStringNotContainsString('<script', $cleaned);
        $this->assertStringNotContainsString('alert(', $cleaned);
        $this->assertStringContainsString('Bonjour', $cleaned);
        $this->assertStringContainsString('fin', $cleaned);
    }

    #[Test]
    public function it_removes_a_script_element_that_is_never_closed(): void
    {
        $this->assertSame('', Briefing::clean('<script>alert(1)'));
        $this->assertSame('', Briefing::clean("<script\ntype=\"text/javascript\">alert(1)</script>"));
    }

    #[Test]
    public function it_removes_a_tag_but_keeps_the_text_it_wrapped(): void
    {
        $this->assertSame('salut', Briefing::clean('<div onmouseover=alert(1)>salut</div>'));
        $this->assertSame('lien', Briefing::clean('<a href="#" onclick="e">lien</a>'));
        $this->assertSame('', Briefing::clean('<img src=x onerror=alert(1)>'));
    }

    #[Test]
    public function it_leaves_a_comparison_untouched(): void
    {
        $written = '3 < 5 et a > b, x <= 10 et 5<6, j <3 les backyards';

        $this->assertSame($written, Briefing::clean($written));
    }

    #[Test]
    public function it_leaves_restricted_markdown_untouched(): void
    {
        $written = "# Titre\n\n- un\n- deux\n\n**gras** et _ital_ 🎉 [lien](https://x.test/a?b=1&c=2)";

        $this->assertSame($written, Briefing::clean($written));
    }

    #[Test]
    public function it_strips_an_executable_scheme_from_a_link(): void
    {
        $this->assertStringNotContainsString('javascript:', Briefing::clean('[clic](javascript:alert(1))'));
        $this->assertStringNotContainsString('javascript:', Briefing::clean('<javascript:alert(1)>'));
        $this->assertStringNotContainsString('data:', Briefing::clean('[clic](data:text/html;base64,AAA)'));
    }

    #[Test]
    public function it_renders_headings_lists_bold_and_links(): void
    {
        $html = Briefing::toHtml("# Titre\n\n- un\n\n**gras** [lien](https://x.test/a)");

        $this->assertStringContainsString('<h1>Titre</h1>', $html);
        $this->assertStringContainsString('<li>un</li>', $html);
        $this->assertStringContainsString('<strong>gras</strong>', $html);
        $this->assertStringContainsString('<a href="https://x.test/a">lien</a>', $html);
    }

    #[Test]
    public function it_renders_a_link_of_mixed_case_javascript_without_a_target(): void
    {
        $this->assertSame("<p><a>x</a></p>\n", Briefing::toHtml('[x](JaVaScRiPt:alert(1))'));
    }

    /** Decoding entities before cleaning would rebuild the very tag the cleaning forbids. */
    #[Test]
    public function it_renders_an_encoded_script_as_visible_text(): void
    {
        $html = Briefing::toHtml(Briefing::clean('&lt;script&gt;alert(1)&lt;/script&gt;'));

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    #[Test]
    public function it_removes_a_tag_hidden_inside_another_tag(): void
    {
        $cleaned = Briefing::clean('<scr<script>ipt>alert(1)</script>');

        $this->assertStringNotContainsString('script', $cleaned);
        $this->assertStringNotContainsString('alert(', $cleaned);
    }
}
