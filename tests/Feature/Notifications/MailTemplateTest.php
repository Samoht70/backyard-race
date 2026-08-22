<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Notifications\RegistrationConfirmed;
use App\Notifications\RegistrationLink;
use Illuminate\Mail\Markdown;
use Illuminate\Notifications\Messages\MailMessage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\CssTokens;
use Tests\Support\Srgb;
use Tests\TestCase;

class MailTemplateTest extends TestCase
{
    private const LINK = 'https://backyard-race.fr/account/edit?signature=7f3ac9e1b2d4';

    private const CODE = 'K7QP-3M9X-RTBD';

    /**
     * @return array<string, array{string, string}>
     */
    public static function paletteDeclarations(): array
    {
        return [
            'la dalle autour du message' => ['background', 'background-color: :hex'],
            'la fenêtre du message' => ['card', 'background-color: :hex'],
            'le filet de la fenêtre' => ['border', 'border: 1px solid :hex'],
            'l’encre du texte' => ['foreground', 'color: :hex'],
            'le fond du bouton' => ['foreground', 'background-color: :hex'],
            'l’encre en retrait du pied' => ['muted-foreground', 'color: :hex'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function packageEnglish(): array
    {
        return [
            'le repli sous le bouton' => ["If you're having trouble clicking"],
            'le pied' => ['All rights reserved'],
            'la salutation' => ['Regards,'],
            'le bonjour' => ['Hello!'],
            'le titre d’erreur' => ['Whoops!'],
        ];
    }

    #[Test]
    #[DataProvider('paletteDeclarations')]
    public function it_dresses_the_mail_in_the_colours_of_the_site(string $token, string $declaration): void
    {
        $palette = CssTokens::fromStylesheet(resource_path('css/app.css'))->theme('light');

        $this->assertArrayHasKey($token, $palette);

        $this->assertStringContainsString(
            str_replace(':hex', Srgb::hex($palette[$token]), $declaration),
            $this->html($this->linkMail()),
        );
    }

    #[Test]
    #[DataProvider('packageEnglish')]
    public function it_leaves_no_english_wrapping_in_either_mail(string $english): void
    {
        $this->assertStringNotContainsString($english, $this->html($this->linkMail()));
        $this->assertStringNotContainsString($english, $this->html($this->confirmationMail()));
    }

    #[Test]
    public function it_prints_the_registration_link_under_the_button(): void
    {
        $html = $this->html($this->linkMail());

        $this->assertStringContainsString('Si le bouton « Finaliser mon inscription » ne fonctionne pas', $html);
        $this->assertStringContainsString('<a href="'.self::LINK.'" class="break-all"', $html);
        $this->assertStringContainsString('>'.self::LINK.'</a>', $html);
    }

    #[Test]
    public function it_asks_nothing_of_a_third_party_when_the_mail_opens(): void
    {
        $html = $this->html($this->confirmationMail());

        $this->assertStringNotContainsString('src=', $html);
        $this->assertStringNotContainsString('url(', $html);
        $this->assertStringNotContainsString('@import', $html);

        preg_match_all('/href="([^"]+)"/', $html, $links);

        foreach ($links[1] as $link) {
            $this->assertStringStartsWith((string) config('app.url'), $link);
        }
    }

    #[Test]
    public function it_gives_the_access_code_a_block_of_its_own(): void
    {
        $html = $this->html($this->confirmationMail());

        $this->assertMatchesRegularExpression(
            '/<span class="code-value"[^>]*>'.preg_quote(self::CODE, '/').'<\/span>/',
            $html,
        );
        $this->assertStringContainsString('white-space: nowrap', $html);
    }

    #[Test]
    public function it_keeps_the_text_version_of_the_link_mail_free_of_markup(): void
    {
        $text = $this->text($this->linkMail());

        $this->assertStringContainsString(self::LINK, $text);
        $this->assertStringNotContainsString('](', $text);
        $this->assertStringNotContainsString('**', $text);
        $this->assertStringNotContainsString('<', $text);
        $this->assertDoesNotMatchRegularExpression('/^#+ /m', $text);
    }

    #[Test]
    public function it_keeps_the_access_code_alone_on_its_line_in_the_text_version(): void
    {
        $text = $this->text($this->confirmationMail());

        $this->assertStringNotContainsString('**', $text);
        $this->assertMatchesRegularExpression('/^'.preg_quote(self::CODE, '/').'$/m', $text);
    }

    private function linkMail(): MailMessage
    {
        return new RegistrationLink(self::LINK, 48)->toMail($this->runner());
    }

    private function confirmationMail(): MailMessage
    {
        return new RegistrationConfirmed(self::CODE)->toMail($this->runner());
    }

    private function runner(): User
    {
        return User::factory()->make(['first_name' => 'Camille']);
    }

    private function html(MailMessage $mail): string
    {
        return (string) $mail->render();
    }

    private function text(MailMessage $mail): string
    {
        return (string) app(Markdown::class)->renderText($mail->markdown, $mail->data());
    }
}
