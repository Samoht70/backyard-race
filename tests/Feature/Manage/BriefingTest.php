<?php

namespace Tests\Feature\Manage;

use App\Enums\Permission;
use App\Models\Event;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BriefingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_briefing_form_to_a_manager(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->registration()->create(['briefing' => '# Consignes']);

        $response = $this->actingAs($this->manager())->get(route('manage.briefing.edit'));

        $response->assertOk();
        $response->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('manage/Briefing')
                ->where('markdown', '# Consignes')
                ->where('isEditable', true),
        );
    }

    #[Test]
    public function it_publishes_the_content_written_by_the_manager(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->registration()->create();

        $this->actingAs($this->manager())
            ->put(route('manage.briefing.update'), ['briefing' => '# Départ à 13 h'])
            ->assertRedirect(route('manage.briefing.edit'));

        $this->actingAs(User::factory()->participant()->create())
            ->get(route('briefing.show'))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('html', "<h1>Départ à 13 h</h1>\n"),
            );
    }

    #[Test]
    public function it_refuses_the_update_to_a_participant(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->registration()->create(['briefing' => '# Consignes']);

        $this->actingAs(User::factory()->participant()->create())
            ->put(route('manage.briefing.update'), ['briefing' => '# Autre chose'])
            ->assertForbidden();

        $this->assertSame('# Consignes', Event::query()->sole()->briefing);
    }

    #[Test]
    public function it_strips_a_script_from_the_stored_briefing(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->registration()->create();

        $this->actingAs($this->manager())
            ->put(route('manage.briefing.update'), [
                'briefing' => "## Règles\n\n<script>alert('x')</script>\n\nBonne course",
            ])
            ->assertRedirect(route('manage.briefing.edit'));

        $briefing = Event::query()->sole()->briefing;

        $this->assertNotNull($briefing);
        $this->assertStringNotContainsString('<script', $briefing);
        $this->assertStringNotContainsString('alert(', $briefing);
        $this->assertStringContainsString('## Règles', $briefing);
        $this->assertStringContainsString('Bonne course', $briefing);
    }

    #[Test]
    public function it_refuses_a_briefing_that_was_only_markup(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->registration()->create(['briefing' => '# Consignes']);

        $this->actingAs($this->manager())
            ->put(route('manage.briefing.update'), ['briefing' => '<script>alert(1)</script>'])
            ->assertSessionHasErrors('briefing');

        $this->assertSame('# Consignes', Event::query()->sole()->briefing);
    }

    #[Test]
    public function it_refuses_the_holder_of_the_event_permission_alone(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->registration()->create();

        $this->actingAs($this->holderOf(Permission::ManageEvent))
            ->put(route('manage.briefing.update'), ['briefing' => '# Consignes'])
            ->assertForbidden();
    }

    #[Test]
    public function it_admits_the_holder_of_the_documents_permission_alone(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->registration()->create();

        $this->actingAs($this->holderOf(Permission::ManageDocuments))
            ->put(route('manage.briefing.update'), ['briefing' => '# Consignes'])
            ->assertRedirect(route('manage.briefing.edit'));
    }

    #[Test]
    public function it_freezes_the_briefing_once_the_event_is_finished(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->finished()->create(['briefing' => '# Consignes']);

        $this->actingAs($this->manager())
            ->get(route('manage.briefing.edit'))
            ->assertInertia(
                fn (AssertableInertia $page) => $page->where('isEditable', false),
            );

        $this->actingAs($this->manager())
            ->put(route('manage.briefing.update'), ['briefing' => '# Autre chose'])
            ->assertForbidden();

        $this->assertSame('# Consignes', Event::query()->sole()->briefing);
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
