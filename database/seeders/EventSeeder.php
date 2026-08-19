<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    private const NAME = 'Backyard des 40 ans';

    /**
     * Guarded on existence rather than updateOrCreate: a second run must never
     * overwrite what the manager has since configured.
     *
     * The factory writes the status, which is not fillable. Going through
     * create() instead would have depended on `db:seed` unguarding the models
     * — true today, but it made the seeded status hinge on how the seeder was
     * invoked rather than on what it says.
     */
    public function run(): void
    {
        if (Event::query()->where('name', self::NAME)->exists()) {
            return;
        }

        Event::factory()->registration()->create(['name' => self::NAME]);
    }
}
