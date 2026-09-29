<?php

namespace App\Services;

use App\Models\ContractPlan;
use App\Models\ServicePrice;
use App\Support\Settings;
use Carbon\CarbonImmutable;

/**
 * كل الأسعار تُحسب هنا في الخادم — التطبيق لا يرسل أي مبلغ.
 */
class PricingService
{
    /** @return array{unit_price: string, quantity: int, subtotal: string, tax: string, total: string, currency: string} */
    public function quoteBooking(ServicePrice $price, int $quantity = 1): array
    {
        $subtotal = round((float) $price->amount * $quantity, 2);
        $tax = round($subtotal * (float) Settings::get('tax_rate') / 100, 2);

        return [
            'unit_price' => $this->money($price->amount),
            'quantity' => $quantity,
            'subtotal' => $this->money($subtotal),
            'tax' => $this->money($tax),
            'total' => $this->money($subtotal + $tax),
            'currency' => $price->currency,
        ];
    }

    /** @return array{start_date: string, end_date: string, months: int, monthly_price: string, total: string, currency: string} */
    public function quoteContract(ContractPlan $plan, CarbonImmutable $start, int $months): array
    {
        return [
            'start_date' => $start->toDateString(),
            'end_date' => $this->contractEndDate($start, $months)->toDateString(),
            'months' => $months,
            'monthly_price' => $this->money($plan->monthly_price),
            'total' => $this->money((float) $plan->monthly_price * $months),
            'currency' => $plan->currency,
        ];
    }

    /** شهر من 10 أكتوبر ينتهي 9 نوفمبر (آخر يوم مشمول). */
    public function contractEndDate(CarbonImmutable $start, int $months): CarbonImmutable
    {
        return $start->addMonthsNoOverflow($months)->subDay();
    }

    public function money(float|string $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }
}
