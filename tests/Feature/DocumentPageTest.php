<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Event;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DocumentPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config()->string('media-library.disk_name'));
    }

    #[Test]
    public function it_redirects_a_guest_to_the_login_page(): void
    {
        Event::factory()->registration()->create();

        $this->get(route('documents.index'))->assertRedirect(route('login'));
    }

    #[Test]
    public function it_lists_the_documents_for_a_participant(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->deposit(Event::factory()->registration()->create());

        $response = $this->actingAs(User::factory()->participant()->create())
            ->get(route('documents.index'));

        $response->assertOk();
        $response->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Documents')
                ->has('documents', 1)
                ->where('documents.0.title', 'Règlement de la course')
                ->where('documents.0.description', 'Les règles de la nuit.')
                ->where('documents.0.file_name', 'reglement.pdf'),
        );
    }

    #[Test]
    public function it_hands_the_participant_a_link_to_the_file(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->deposit(Event::factory()->registration()->create());

        $this->actingAs(User::factory()->participant()->create())
            ->get(route('documents.index'))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('documents.0.url', fn (?string $url): bool => is_string($url) && $url !== ''),
            );
    }

    #[Test]
    public function it_refuses_a_draft_event_to_a_participant(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->deposit(Event::factory()->create());

        $this->actingAs(User::factory()->participant()->create())
            ->get(route('documents.index'))
            ->assertForbidden();
    }

    #[Test]
    public function it_shows_a_draft_event_to_the_manager(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->deposit(Event::factory()->create());

        $this->actingAs(User::factory()->manager()->create())
            ->get(route('documents.index'))
            ->assertOk();
    }

    #[Test]
    public function it_says_so_when_nothing_has_been_deposited(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->registration()->create();

        $this->actingAs(User::factory()->participant()->create())
            ->get(route('documents.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('documents', 0));
    }

    private function deposit(Event $event): void
    {
        $document = $event->documents()->create([
            'title' => 'Règlement de la course',
            'description' => 'Les règles de la nuit.',
        ]);

        $path = tempnam(sys_get_temp_dir(), 'doc');
        copy(database_path('seeders/files/reglement.pdf'), $path);

        $document->addMedia(new UploadedFile($path, 'reglement.pdf', null, null, true))
            ->toMediaCollection(Document::FILE_COLLECTION);
    }
}
