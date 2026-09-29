<?php

namespace Database\Factories;

use App\Enums\AdminRole;
use App\Models\AdminUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<AdminUser>
 */
class AdminUserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'role' => AdminRole::Operations,
            'is_active' => true,
        ];
    }

    public function superAdmin(): static
    {
        return $this->state(['role' => AdminRole::SuperAdmin]);
    }

    public function support(): static
    {
        return $this->state(['role' => AdminRole::Support]);
    }
}
