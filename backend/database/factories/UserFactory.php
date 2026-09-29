<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => fake()->unique()->numerify('05########'),
            'email' => null,
            'password' => static::$password ??= Hash::make('password'),
            'role' => UserRole::Customer,
            'status' => 'active',
            'locale' => 'ar',
            'phone_verified_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    public function customer(): static
    {
        return $this->state(['role' => UserRole::Customer]);
    }

    public function worker(): static
    {
        return $this->state(['role' => UserRole::Worker]);
    }

    public function suspended(): static
    {
        return $this->state(['status' => 'suspended']);
    }
}
