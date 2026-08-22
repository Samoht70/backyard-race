<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    private const DIAGNOSTIC_MESSAGE = 'Le presse-étoupe a lâché';

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->group(function (): void {
            Route::get('failing', fn () => throw new RuntimeException(self::DIAGNOSTIC_MESSAGE));
            Route::get('expiring', fn () => throw new TokenMismatchException);
        });
    }

    #[Test]
    public function it_renders_an_unknown_address_in_the_site(): void
    {
        $response = $this->get('/nowhere');

        $response->assertNotFound();
        $response->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Error')
                ->where('status', 404),
        );
    }

    #[Test]
    public function it_renders_a_refused_access_in_the_site(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $response = $this->actingAs(User::factory()->participant()->create())
            ->get(route('manage.index'));

        $response->assertForbidden();
        $response->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Error')
                ->where('status', 403),
        );
    }

    #[Test]
    public function it_renders_an_expired_page_in_the_site(): void
    {
        $response = $this->get('expiring');

        $response->assertStatus(419);
        $response->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Error')
                ->where('status', 419),
        );
    }

    #[Test]
    public function it_renders_a_server_error_in_the_site_without_the_diagnostic(): void
    {
        config(['app.debug' => false]);

        $response = $this->get('failing');

        $response->assertStatus(500);
        $response->assertDontSee(self::DIAGNOSTIC_MESSAGE);
        $response->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Error')
                ->where('status', 500),
        );
    }

    #[Test]
    public function it_keeps_the_diagnostic_screen_in_development(): void
    {
        config(['app.debug' => true]);

        $response = $this->get('failing');

        $response->assertStatus(500);
        $response->assertSee(self::DIAGNOSTIC_MESSAGE, false);
    }

    #[Test]
    public function it_leaves_a_json_call_as_json(): void
    {
        $response = $this->getJson('/nowhere');

        $response->assertNotFound();
        $response->assertHeader('content-type', 'application/json');
        $response->assertJsonStructure(['message']);
    }
}
