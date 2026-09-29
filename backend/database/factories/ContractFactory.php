<?php

namespace Database\Factories;

use App\Enums\ContractStatus;
use App\Models\Address;
use App\Models\Contract;
use App\Models\ContractPlan;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contract>
 */
class ContractFactory extends Factory
{
    public function definition(): array
    {
        $start = now()->addDays(3)->startOfDay();

        return [
            'customer_id' => Customer::factory(),
            'plan_id' => ContractPlan::factory(),
            'address_id' => fn (array $a) => Address::factory()->create(['customer_id' => $a['customer_id']])->id,
            'address_snapshot' => fn (array $a) => Address::find($a['address_id'])->toSnapshot(),
            'plan_snapshot' => fn (array $a) => ContractPlan::find($a['plan_id'])->toSnapshot(),
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->addMonth()->subDay()->toDateString(),
            'months' => 1,
            'monthly_price' => fn (array $a) => ContractPlan::find($a['plan_id'])->monthly_price,
            'total_amount' => fn (array $a) => $a['monthly_price'] * $a['months'],
            'currency' => config('agency.currency'),
            'status' => ContractStatus::Pending,
            'terms_version' => config('agency.contracts.terms_version'),
            'terms_accepted_at' => now(),
        ];
    }

    public function status(ContractStatus $status): static
    {
        return $this->state(['status' => $status]);
    }
}
