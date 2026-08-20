<?php

namespace Tests\Feature\Manage;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ManageAccessTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_redirects_a_guest_to_the_login_page(): void
    {
        $response = $this->get(route('manage.index'));

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function it_refuses_a_participant(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $response = $this->actingAs(User::factory()->participant()->create())
            ->get(route('manage.index'));

        $response->assertForbidden();
    }

    #[Test]
    public function it_admits_a_manager(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $response = $this->actingAs(User::factory()->manager()->create())
            ->get(route('manage.index'));

        $response->assertOk();
        $response->assertInertia(
            fn (AssertableInertia $page) => $page->component('manage/Index'),
        );
    }

    #[Test]
    public function it_refuses_a_user_carrying_no_role(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('manage.index'));

        $response->assertForbidden();
    }

    #[Test]
    public function it_refuses_when_the_permissions_were_never_seeded(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('manage.index'));

        $response->assertForbidden();
    }

    #[Test]
    public function it_refuses_the_registrations_when_the_permissions_were_never_seeded(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('manage.registrations.index'));

        $response->assertForbidden();
    }
}
