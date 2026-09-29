<?php

namespace Database\Factories;

use App\Enums\WorkerStatus;
use App\Enums\WorkerType;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Worker>
 */
class WorkerFactory extends Factory
{
    /** الافتراضي: خادمة (عقود). استخدم cleaner() لعضو فريق زيارات. */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->worker(),
            'type' => WorkerType::Housekeeper,
            'national_id' => fake()->numerify('##########'),
            'status' => WorkerStatus::Active,
        ];
    }

    public function cleaner(): static
    {
        return $this->state(['type' => WorkerType::Cleaner]);
    }

    public function housekeeper(): static
    {
        return $this->state(['type' => WorkerType::Housekeeper]);
    }

    public function onLeave(): static
    {
        return $this->state(['status' => WorkerStatus::OnLeave]);
    }
}
