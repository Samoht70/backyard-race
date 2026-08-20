<?php

namespace Tests\Unit\Rules;

use App\Rules\DocumentFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Translation\PotentiallyTranslatedString;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DocumentFileTest extends TestCase
{
    #[Test]
    #[DataProvider('acceptedFiles')]
    public function it_accepts_a_file_whose_content_matches_its_extension(string $fixture): void
    {
        $this->assertSame([], $this->failures($this->upload($fixture)));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function acceptedFiles(): array
    {
        return [
            'pdf' => ['reglement.pdf'],
            'gpx read as xml' => ['boucle.gpx'],
        ];
    }

    #[Test]
    public function it_refuses_a_gpx_track_renamed_as_a_pdf(): void
    {
        $failures = $this->failures($this->upload('boucle.gpx', 'reglement.pdf'));

        $this->assertSame(['document.file.mismatch'], $failures);
    }

    #[Test]
    public function it_refuses_an_extension_outside_the_list(): void
    {
        $failures = $this->failures($this->upload('reglement.pdf', 'reglement.exe'));

        $this->assertSame(['document.file.extension'], $failures);
    }

    #[Test]
    public function it_refuses_anything_that_is_not_an_uploaded_file(): void
    {
        $this->assertSame(['document.file.unreadable'], $this->failures('reglement.pdf'));
    }

    #[Test]
    public function it_offers_every_accepted_mime_type_to_the_media_collection(): void
    {
        $this->assertContains('application/pdf', DocumentFile::mimeTypes());
        $this->assertContains('text/xml', DocumentFile::mimeTypes());
        $this->assertSame(DocumentFile::mimeTypes(), array_values(array_unique(DocumentFile::mimeTypes())));
    }

    /**
     * @return list<string>
     */
    private function failures(mixed $upload): array
    {
        $failures = [];

        new DocumentFile()->validate('file', $upload, function (string $message) use (&$failures) {
            $failures[] = $message;

            return new PotentiallyTranslatedString($message, app('translator'));
        });

        return $failures;
    }

    private function upload(string $fixture, ?string $as = null): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'doc');

        copy(database_path("seeders/files/{$fixture}"), $path);

        return new UploadedFile($path, $as ?? $fixture, null, null, true);
    }
}
