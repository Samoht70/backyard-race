<?php

namespace Tests\Feature;

use App\Models\Document;
use Database\Seeders\DocumentSeeder;
use Database\Seeders\EventSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DocumentSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config()->string('media-library.disk_name'));
    }

    #[Test]
    public function it_deposits_the_rules_and_the_track(): void
    {
        $this->seed(EventSeeder::class);
        $this->seed(DocumentSeeder::class);

        $files = Document::query()->get()->map(fn (Document $document): ?string => $document->file()?->file_name);

        $this->assertEqualsCanonicalizing(['reglement.pdf', 'boucle.gpx'], $files->all());
    }

    #[Test]
    public function it_does_nothing_without_an_event(): void
    {
        $this->seed(DocumentSeeder::class);

        $this->assertDatabaseCount('documents', 0);
    }

    #[Test]
    public function it_does_not_duplicate_the_documents_on_a_second_run(): void
    {
        $this->seed(EventSeeder::class);
        $this->seed(DocumentSeeder::class);
        $this->seed(DocumentSeeder::class);

        $this->assertSame(2, Document::query()->count());
    }
}
