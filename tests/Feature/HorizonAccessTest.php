<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HorizonAccessTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_opens_the_queue_dashboard_to_the_manager(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertTrue(Gate::forUser(User::factory()->manager()->create())->allows('viewHorizon'));
    }

    #[Test]
    public function it_refuses_the_queue_dashboard_to_a_participant(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertFalse(Gate::forUser(User::factory()->participant()->create())->allows('viewHorizon'));
    }

    #[Test]
    public function it_refuses_the_queue_dashboard_to_a_guest(): void
    {
        $this->assertFalse(Gate::allows('viewHorizon'));
    }
}
