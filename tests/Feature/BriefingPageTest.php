<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BriefingPageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_redirects_a_guest_to_the_login_page(): void
    {
        Event::factory()->registration()->create();

        $this->get(route('briefing.show'))->assertRedirect(route('login'));
    }

    #[Test]
    public function it_shows_the_published_briefing_to_a_participant(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->event('# Consignes'."\n\n".'- Lampe frontale');

        $response = $this->actingAs(User::factory()->participant()->create())
            ->get(route('briefing.show'));

        $response->assertOk();
        $response->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Briefing')
                ->where('html', "<h1>Consignes</h1>\n<ul>\n<li>Lampe frontale</li>\n</ul>\n"),
        );
    }

    #[Test]
    public function it_refuses_a_draft_event_to_a_participant(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->create();

        $this->actingAs(User::factory()->participant()->create())
            ->get(route('briefing.show'))
            ->assertForbidden();
    }

    #[Test]
    public function it_shows_a_draft_event_to_the_manager(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::factory()->create();

        $this->actingAs(User::factory()->manager()->create())
            ->get(route('briefing.show'))
            ->assertOk();
    }

    #[Test]
    public function it_falls_back_to_the_initial_briefing_when_none_was_written(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        foreach ([null, ''] as $written) {
            $this->event($written);

            $response = $this->actingAs(User::factory()->participant()->create())
                ->get(route('briefing.show'));

            $response->assertOk();
            $response->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('html', fn (string $html): bool => str_contains($html, 'Backyard Ultra du Quart de Siècle')),
            );

            Event::query()->delete();
        }
    }

    #[Test]
    public function it_renders_a_submitted_script_as_visible_text(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->event('&lt;script&gt;alert(1)&lt;/script&gt;');

        $response = $this->actingAs(User::factory()->participant()->create())
            ->get(route('briefing.show'));

        $response->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('html', fn (string $html): bool => ! str_contains($html, '<script')),
        );
    }

    private function event(?string $briefing): Event
    {
        return Event::factory()->registration()->create(['briefing' => $briefing]);
    }
}
