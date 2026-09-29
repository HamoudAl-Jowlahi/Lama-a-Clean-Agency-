<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->customer(),
        ];
    }

    /** عميل بعنوان افتراضي. */
    public function withAddress(): static
    {
        return $this->afterCreating(function (Customer $customer) {
            $address = Address::factory()->for($customer)->create();
            $customer->update(['default_address_id' => $address->id]);
        });
    }
}
