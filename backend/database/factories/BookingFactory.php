<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Address;
use App\Models\Booking;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'address_id' => fn (array $a) => Address::factory()->create(['customer_id' => $a['customer_id']])->id,
            'address_snapshot' => fn (array $a) => Address::find($a['address_id'])->toSnapshot(),
            'status' => BookingStatus::Pending,
            'scheduled_date' => now()->addDays(2)->toDateString(),
            'scheduled_time' => '10:00',
            'subtotal' => 250,
            'tax' => 0,
            'discount' => 0,
            'total' => 250,
            'currency' => config('agency.currency'),
        ];
    }

    public function status(BookingStatus $status): static
    {
        return $this->state(['status' => $status]);
    }
}
