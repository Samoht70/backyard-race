<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Event;
use Database\Seeders\EventSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EventSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_the_birthday_event_ready_for_registrations(): void
    {
        $this->seed(EventSeeder::class);

        $event = Event::query()->sole();

        $this->assertSame(EventStatus::Registration, $event->status);
        $this->assertNotNull($event->first_start_at);
        $this->assertNotNull($event->lap_distance_meters);
        $this->assertNotNull($event->lap_duration_minutes);
    }

    #[Test]
    public function it_does_not_duplicate_the_event_on_a_second_run(): void
    {
        $this->seed(EventSeeder::class);
        $this->seed(EventSeeder::class);

        $this->assertSame(1, Event::query()->count());
    }

    #[Test]
    public function it_keeps_the_manager_edits_made_since_the_first_run(): void
    {
        $this->seed(EventSeeder::class);

        Event::query()->sole()->update(['lap_distance_meters' => 5000]);

        $this->seed(EventSeeder::class);

        $this->assertSame(5000, Event::query()->sole()->lap_distance_meters);
    }
}
