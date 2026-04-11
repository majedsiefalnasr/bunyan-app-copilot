<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'phone' => fake()->boolean(70) ? fake()->e164PhoneNumber() : null,
            'is_active' => true,
            'avatar' => null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => ['role' => UserRole::ADMIN])
            ->afterCreating(function (User $user) {
                $role = Role::where('name', 'admin')->first();
                if ($role !== null) {
                    $user->roles()->attach($role->id);
                }
            });
    }

    public function customer(): static
    {
        return $this->state(fn (array $attributes) => ['role' => UserRole::CUSTOMER])
            ->afterCreating(function (User $user) {
                $role = Role::where('name', 'customer')->first();
                if ($role !== null) {
                    $user->roles()->attach($role->id);
                }
            });
    }

    public function contractor(): static
    {
        return $this->state(fn (array $attributes) => ['role' => UserRole::CONTRACTOR])
            ->afterCreating(function (User $user) {
                $role = Role::where('name', 'contractor')->first();
                if ($role !== null) {
                    $user->roles()->attach($role->id);
                }
            });
    }

    public function supervisingArchitect(): static
    {
        return $this->state(fn (array $attributes) => ['role' => UserRole::SUPERVISING_ARCHITECT])
            ->afterCreating(function (User $user) {
                $role = Role::where('name', 'supervising_architect')->first();
                if ($role !== null) {
                    $user->roles()->attach($role->id);
                }
            });
    }

    public function fieldEngineer(): static
    {
        return $this->state(fn (array $attributes) => ['role' => UserRole::FIELD_ENGINEER])
            ->afterCreating(function (User $user) {
                $role = Role::where('name', 'field_engineer')->first();
                if ($role !== null) {
                    $user->roles()->attach($role->id);
                }
            });
    }
}
