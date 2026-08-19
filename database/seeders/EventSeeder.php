<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    private const NAME = 'Backyard des 40 ans';

    /**
     * firstOrCreate rather than updateOrCreate: a second run must not overwrite
     * what the manager has since configured.
     */
    public function run(): void
    {
        Event::query()->firstOrCreate(
            ['name' => self::NAME],
            Event::factory()->registration()->make(['name' => self::NAME])->getAttributes(),
        );
    }
}
