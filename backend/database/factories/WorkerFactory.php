<?php

namespace Database\Factories;

use App\Enums\WorkerStatus;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Worker>
 */
class WorkerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->worker(),
            'national_id' => fake()->numerify('##########'),
            'status' => WorkerStatus::Active,
        ];
    }

    public function onLeave(): static
    {
        return $this->state(['status' => WorkerStatus::OnLeave]);
    }
}
