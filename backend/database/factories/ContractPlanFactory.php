<?php

namespace Database\Factories;

use App\Models\ContractPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContractPlan>
 */
class ContractPlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name_ar' => 'دوام كامل',
            'work_days_per_week' => 6,
            'hours_per_day' => 8,
            'monthly_price' => 1800,
            'currency' => config('agency.currency'),
            'min_months' => 1,
            'max_months' => 12,
            'is_active' => true,
        ];
    }
}
