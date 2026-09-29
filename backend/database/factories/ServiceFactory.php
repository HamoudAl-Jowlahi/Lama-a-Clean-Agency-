<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name_ar' => 'خدمة '.fake()->word(),
            'icon' => 'home',
            'duration_minutes' => 120,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
