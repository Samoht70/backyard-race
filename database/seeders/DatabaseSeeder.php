<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        User::factory()->manager()->create(['email' => 'manager@backyard.test']);
        User::factory()->participant()->create(['email' => 'participant@backyard.test']);
        User::factory()->count(29)->participant()->create();
    }
}
