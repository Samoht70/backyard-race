<?php

namespace Tests\Feature\Manage;

use App\Enums\Permission;
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

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config()->string('media-library.disk_name'));
    }

    #[Test]
    public function it_stores_a_document_deposited_by_the_manager(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->registration()->create();

        $this->actingAs($this->manager())
            ->post(route('manage.documents.store'), [
                'title' => 'Règlement de la course',
                'description' => 'Les règles de la nuit.',
                'file' => $this->fixture('reglement.pdf'),
            ])
            ->assertRedirect(route('manage.documents.index'));

        $document = Document::query()->sole();

        $this->assertSame('Règlement de la course', $document->title);
        $this->assertSame('Les règles de la nuit.', $document->description);
        $this->assertNotNull($document->file());
        Storage::disk($document->file()->disk)->assertExists($document->file()->getPathRelativeToRoot());
    }

    /** The manager names the document; the stored file keeps the name it was uploaded under. */
    #[Test]
    public function it_leaves_the_uploaded_file_name_alone(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->registration()->create();

        $this->actingAs($this->manager())
            ->post(route('manage.documents.store'), [
                'title' => 'Un titre qui ne ressemble pas au fichier',
                'file' => $this->fixture('reglement.pdf'),
            ]);

        $this->assertSame('reglement.pdf', Document::query()->sole()->file()?->file_name);
    }

    #[Test]
    public function it_accepts_a_gpx_track_read_as_xml(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->registration()->create();

        $this->actingAs($this->manager())
            ->post(route('manage.documents.store'), [
                'title' => 'Trace de la boucle',
                'file' => $this->fixture('boucle.gpx'),
            ])
            ->assertRedirect(route('manage.documents.index'));

        $this->assertSame('boucle.gpx', Document::query()->sole()->file()?->file_name);
    }

    #[Test]
    public function it_refuses_a_file_whose_real_type_belies_its_extension(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->registration()->create();

        $this->actingAs($this->manager())
            ->post(route('manage.documents.store'), [
                'title' => 'Règlement',
                'file' => $this->fixture('boucle.gpx', 'reglement.pdf'),
            ])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('documents', 0);
        $this->assertDatabaseCount('media', 0);
    }

    #[Test]
    public function it_refuses_an_extension_that_is_not_allowed(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->registration()->create();

        $this->actingAs($this->manager())
            ->post(route('manage.documents.store'), [
                'title' => 'Feuille de calcul',
                'file' => UploadedFile::fake()->create('tableau.xlsx', 12),
            ])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('documents', 0);
    }

    #[Test]
    public function it_refuses_a_file_over_the_size_limit(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->registration()->create();

        $this->actingAs($this->manager())
            ->post(route('manage.documents.store'), [
                'title' => 'Règlement',
                'file' => UploadedFile::fake()->create('reglement.pdf', 11 * 1024, 'application/pdf'),
            ])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('documents', 0);
    }

    #[Test]
    public function it_removes_the_stored_file_along_with_the_document(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $document = $this->deposited();
        $file = $document->file();

        $this->assertNotNull($file);
        $disk = $file->disk;
        $path = $file->getPathRelativeToRoot();

        $this->actingAs($this->manager())
            ->delete(route('manage.documents.destroy', $document))
            ->assertRedirect(route('manage.documents.index'));

        $this->assertDatabaseCount('documents', 0);
        Storage::disk($disk)->assertMissing($path);
    }

    #[Test]
    public function it_refuses_the_deposit_to_a_participant(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->registration()->create();

        $this->actingAs(User::factory()->participant()->create())
            ->post(route('manage.documents.store'), [
                'title' => 'Règlement',
                'file' => $this->fixture('reglement.pdf'),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('documents', 0);
    }

    #[Test]
    public function it_refuses_the_holder_of_the_event_permission_alone(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->registration()->create();

        $this->actingAs($this->holderOf(Permission::ManageEvent))
            ->get(route('manage.documents.index'))
            ->assertForbidden();
    }

    #[Test]
    public function it_admits_the_holder_of_the_documents_permission_alone(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->registration()->create();

        $this->actingAs($this->holderOf(Permission::ManageDocuments))
            ->post(route('manage.documents.store'), [
                'title' => 'Règlement',
                'file' => $this->fixture('reglement.pdf'),
            ])
            ->assertRedirect(route('manage.documents.index'));
    }

    #[Test]
    public function it_freezes_the_documents_once_the_event_is_finished(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $document = $this->deposited(Event::factory()->finished()->create());

        $this->actingAs($this->manager())
            ->get(route('manage.documents.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('isEditable', false));

        $this->actingAs($this->manager())
            ->post(route('manage.documents.store'), [
                'title' => 'Règlement',
                'file' => $this->fixture('reglement.pdf'),
            ])
            ->assertForbidden();

        $this->actingAs($this->manager())
            ->delete(route('manage.documents.destroy', $document))
            ->assertForbidden();

        $this->assertDatabaseCount('documents', 1);
    }

    private function deposited(?Event $event = null): Document
    {
        $event ??= Event::factory()->registration()->create();

        $document = $event->documents()->create(['title' => 'Règlement', 'description' => null]);
        $document->addMedia($this->fixture('reglement.pdf'))->toMediaCollection(Document::FILE_COLLECTION);

        return $document->refresh();
    }

    private function fixture(string $name, ?string $as = null): UploadedFile
    {
        $source = database_path("seeders/files/{$name}");
        $copy = tempnam(sys_get_temp_dir(), 'doc');

        copy($source, $copy);

        return new UploadedFile($copy, $as ?? $name, null, null, true);
    }

    private function manager(): User
    {
        return User::factory()->manager()->create();
    }

    private function holderOf(Permission $permission): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permission->value);

        return $user;
    }
}
