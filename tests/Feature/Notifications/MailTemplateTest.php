<?php

namespace Tests\Feature\Notifications;

use App\Enums\RegistrationOutcome;
use App\Listeners\EmbedBrandMarkListener;
use App\Models\User;
use App\Notifications\RegistrationLink;
use App\Notifications\RegistrationProcessed;
use App\Notifications\RegistrationReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Markdown;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Tests\Support\CssTokens;
use Tests\Support\Srgb;
use Tests\TestCase;

class MailTemplateTest extends TestCase
{
    use RefreshDatabase;

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

    /**
     * @return array<string, array{RegistrationOutcome}>
     */
    public static function processingOutcomes(): array
    {
        $outcomes = [];

        foreach (RegistrationOutcome::cases() as $outcome) {
            $outcomes[$outcome->value] = [$outcome];
        }

        return $outcomes;
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
        $this->assertStringNotContainsString($english, $this->html($this->receiptMail()));
    }

    #[Test]
    #[DataProvider('processingOutcomes')]
    public function it_writes_every_line_of_a_processing_mail_in_french_and_without_a_bib(
        RegistrationOutcome $outcome,
    ): void {
        $html = $this->html($this->processingMail($outcome));

        $this->assertStringNotContainsString($outcome->mailKey(), $html);
        $this->assertStringContainsString('Camille', $html);
        $this->assertDoesNotMatchRegularExpression('/dossard[^.]*\d/', $html);

        foreach (self::packageEnglish() as [$english]) {
            $this->assertStringNotContainsString($english, $html);
        }
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
        $html = $this->html($this->receiptMail());

        $this->assertStringContainsString('src="cid:'.EmbedBrandMarkListener::CID.'"', $html);
        $this->assertDoesNotMatchRegularExpression('/src="(?!cid:)/', $html);
        $this->assertStringNotContainsString('url(', $html);
        $this->assertStringNotContainsString('@import', $html);

        preg_match_all('/href="([^"]+)"/', $html, $links);

        foreach ($links[1] as $link) {
            $this->assertStringStartsWith((string) config('app.url'), $link);
        }
    }

    #[Test]
    public function it_carries_the_brand_mark_inside_the_message(): void
    {
        User::factory()->create()->notify(new RegistrationReceived(self::CODE));

        $sent = Mail::getSymfonyTransport();
        $this->assertInstanceOf(ArrayTransport::class, $sent);

        $email = $sent->messages()->first()?->getOriginalMessage();
        $this->assertInstanceOf(Email::class, $email);

        $mark = collect($email->getAttachments())
            ->first(fn (DataPart $part): bool => $part->getName() === EmbedBrandMarkListener::CID);

        $this->assertInstanceOf(DataPart::class, $mark);
        $this->assertSame('image', $mark->getMediaType());
        $this->assertSame('png', $mark->getMediaSubtype());
        $this->assertSame('inline', $mark->getPreparedHeaders()->getHeaderBody('Content-Disposition'));
    }

    #[Test]
    public function it_gives_the_access_code_a_block_of_its_own(): void
    {
        $html = $this->html($this->receiptMail());

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
        $text = $this->text($this->receiptMail());

        $this->assertStringNotContainsString('**', $text);
        $this->assertMatchesRegularExpression('/^'.preg_quote(self::CODE, '/').'$/m', $text);
    }

    private function linkMail(): MailMessage
    {
        return new RegistrationLink(self::LINK, 48)->toMail($this->runner());
    }

    private function receiptMail(): MailMessage
    {
        return new RegistrationReceived(self::CODE)->toMail($this->runner());
    }

    private function processingMail(RegistrationOutcome $outcome): MailMessage
    {
        return new RegistrationProcessed($outcome)->toMail($this->runner());
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
