<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'label' => fake()->randomElement(['المنزل', 'بيت الأهل', 'المكتب']),
            'city' => 'الرياض',
            'district' => fake()->randomElement(['حي النرجس', 'حي الياسمين', 'حي الملقا', 'حي العارض', 'حي الربيع']),
            'street' => 'شارع '.fake()->numberBetween(1, 60),
            'building' => (string) fake()->numberBetween(1, 40),
            'floor' => (string) fake()->numberBetween(0, 5),
            'details' => null,
            'latitude' => fake()->latitude(24.6, 24.9),
            'longitude' => fake()->longitude(46.5, 46.8),
        ];
    }
}
