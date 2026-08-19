<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Exceptions\RoleDoesNotExist;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * @throws RoleDoesNotExist when the roles seeder has not run
     */
    public function manager(): static
    {
        return $this->afterCreating(function (User $user): void {
            $user->assignRole(Role::Manager);
        });
    }

    /**
     * @throws RoleDoesNotExist when the roles seeder has not run
     */
    public function participant(): static
    {
        return $this->afterCreating(function (User $user): void {
            $user->assignRole(Role::Participant);
        });
    }
}
