<?php

namespace Database\Factories;

use App\Enums\PriceUnit;
use App\Models\Service;
use App\Models\ServicePrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServicePrice>
 */
class ServicePriceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'label_ar' => 'السعر الأساسي',
            'unit' => PriceUnit::Fixed,
            'amount' => fake()->randomElement([120, 150, 250, 400]),
            'currency' => config('agency.currency'),
            'is_active' => true,
        ];
    }
}
